<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\DgiiLog;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    /**
     * DGII Modification Codes (Tabla de Códigos de Modificación e-CF / NCF)
     */
    const MODIFICATION_CODES = [
        1 => 'Anulación total del documento modificado',
        2 => 'Corrección de texto en documento modificado',
        3 => 'Corrección de montos (Descuento o devolución)',
        4 => 'Reemplazo de NCF emitido en contingencia',
        5 => 'Referencia a e-CF previo',
    ];

    /**
     * DGII Anulation Types (Tabla 608)
     */
    const ANULATION_TYPES = [
        '01' => 'Deterioro de factura pre-impresa',
        '02' => 'Errores de impresión (Factura pre-impresa)',
        '03' => 'Impresión defectuosa',
        '04' => 'Duplicidad de factura',
        '05' => 'Corrección de la información',
        '06' => 'Cambio de productos',
        '07' => 'Devolución de productos',
        '08' => 'Omisión de productos',
        '09' => 'Errores en secuencias de NCF',
    ];

    /**
     * Trace the entire lineage and audit trail for a voucher.
     * Accepts ?id=X or ?query=ENCF_OR_NUMBER
     */
    public function trace(Request $request)
    {
        $id = $request->input('id');
        $query = trim((string)$request->input('query', ''));

        if (!$id && !$query) {
            return response()->json(['error' => 'Debe especificar un ID o número de comprobante/factura.'], 400);
        }

        $searchedDoc = null;

        if ($id) {
            $searchedDoc = Invoice::with(['client', 'items', 'payments'])->find($id);
        } elseif ($query) {
            // First check exact encf or invoice_number
            $searchedDoc = Invoice::with(['client', 'items', 'payments'])
                ->where('encf', $query)
                ->orWhere('invoice_number', $query)
                ->first();

            // If not found, try partial search
            if (!$searchedDoc) {
                $searchedDoc = Invoice::with(['client', 'items', 'payments'])
                    ->where('encf', 'like', "%{$query}%")
                    ->orWhere('invoice_number', 'like', "%{$query}%")
                    ->first();
            }
        }

        if (!$searchedDoc) {
            return response()->json(['error' => 'No se encontró ningún comprobante con los datos proporcionados.'], 404);
        }

        // Determine Root Invoice (if searchedDoc is a Credit/Debit note, locate parent)
        $rootInvoice = $searchedDoc;
        $isChildDoc = false;

        if (!empty($searchedDoc->modified_ncf)) {
            $parent = Invoice::with(['client', 'items', 'payments'])
                ->where('encf', $searchedDoc->modified_ncf)
                ->orWhere('invoice_number', $searchedDoc->modified_ncf)
                ->first();

            if ($parent) {
                $rootInvoice = $parent;
                $isChildDoc = true;
            }
        }

        // 1. Associated Origin Quote (if converted)
        $originQuote = Quote::with('client')
            ->where('converted_invoice_id', $rootInvoice->id)
            ->first();

        // 2. Modifying Invoices (Notas de Crédito & Débito pointing to this root)
        $modifyingInvoices = Invoice::with(['client', 'payments'])
            ->where(function ($q) use ($rootInvoice) {
                if ($rootInvoice->encf) {
                    $q->where('modified_ncf', $rootInvoice->encf);
                }
                if ($rootInvoice->invoice_number) {
                    $q->orWhere('modified_ncf', $rootInvoice->invoice_number);
                }
            })
            ->orderBy('issue_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 3. Payments
        $payments = Payment::where('invoice_id', $rootInvoice->id)
            ->orderBy('payment_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 4. DGII Logs for Root and Modifiers
        $allInvoiceIds = array_merge([$rootInvoice->id], $modifyingInvoices->pluck('id')->toArray());
        $allEncfs = array_filter(array_merge([$rootInvoice->encf], $modifyingInvoices->pluck('encf')->toArray()));

        $dgiiLogs = DgiiLog::whereIn('invoice_id', $allInvoiceIds)
            ->orWhereIn('encf', $allEncfs)
            ->orderBy('created_at', 'asc')
            ->get();

        // 5. Financial Reconciliation Calculation
        $originalTotal = (float)$rootInvoice->total;
        $creditNotesTotal = 0;
        $debitNotesTotal = 0;

        foreach ($modifyingInvoices as $mod) {
            $modTotal = (float)$mod->total;
            if ($mod->isCreditNote() || (int)$mod->ecf_type === 34) {
                $creditNotesTotal += $modTotal;
            } elseif ((int)$mod->ecf_type === 33) {
                $debitNotesTotal += $modTotal;
            }
        }

        $effectiveInvoicedTotal = max(0, $originalTotal - $creditNotesTotal + $debitNotesTotal);
        $paymentsTotal = (float)$payments->sum('amount');
        $netBalance = max(0, $effectiveInvoicedTotal - $paymentsTotal);

        // Status Determination
        $isCancelled = $rootInvoice->status === 'cancelled';
        $isFullyCredited = ($creditNotesTotal >= $originalTotal && $originalTotal > 0);

        $financialStatus = 'pending';
        $financialStatusLabel = 'Pendiente de Pago';

        if ($isCancelled) {
            $financialStatus = 'cancelled';
            $financialStatusLabel = 'Anulada';
        } elseif ($isFullyCredited) {
            $financialStatus = 'credited';
            $financialStatusLabel = 'Anulada por Nota de Crédito';
        } elseif ($netBalance <= 0.01 && ($paymentsTotal > 0 || $creditNotesTotal > 0)) {
            $financialStatus = 'settled';
            $financialStatusLabel = 'Saldada Totalmente';
        } elseif ($paymentsTotal > 0 || $creditNotesTotal > 0) {
            $financialStatus = 'partial';
            $financialStatusLabel = 'Saldo Parcial';
        }

        // 6. Assemble Timeline Events
        $timeline = [];

        // Event: Quote Created
        if ($originQuote) {
            $timeline[] = [
                'type' => 'quote_created',
                'title' => 'Cotización Creada',
                'subtitle' => "Cotización #{$originQuote->quote_number}",
                'timestamp' => $originQuote->created_at ? $originQuote->created_at->toISOString() : null,
                'date_formatted' => $originQuote->issue_date ? $originQuote->issue_date->format('d/m/Y') : null,
                'badge' => 'Cotización',
                'badge_class' => 'badge-info',
                'icon' => 'quote',
                'amount' => (float)$originQuote->total,
                'currency' => $originQuote->currency ?? 'DOP',
                'details' => [
                    'quote_number' => $originQuote->quote_number,
                    'client_name' => $originQuote->client->name ?? '—',
                    'id' => $originQuote->id,
                ],
            ];
        }

        // Event: Invoice Issued
        $timeline[] = [
            'type' => 'invoice_issued',
            'title' => $rootInvoice->is_ecf ? "e-CF Emitido ({$rootInvoice->encf})" : "Factura Emitida (#{$rootInvoice->invoice_number})",
            'subtitle' => "Comprobante Fiscal base para " . ($rootInvoice->client->name ?? 'Consumidor Final'),
            'timestamp' => $rootInvoice->created_at ? $rootInvoice->created_at->toISOString() : null,
            'date_formatted' => $rootInvoice->issue_date ? $rootInvoice->issue_date->format('d/m/Y') : null,
            'badge' => $rootInvoice->is_ecf ? 'e-CF' : 'Factura',
            'badge_class' => 'badge-primary',
            'icon' => 'invoice',
            'amount' => (float)$rootInvoice->total,
            'currency' => $rootInvoice->currency ?? 'DOP',
            'details' => [
                'invoice_id' => $rootInvoice->id,
                'invoice_number' => $rootInvoice->invoice_number,
                'encf' => $rootInvoice->encf,
                'ecf_type' => $rootInvoice->ecf_type,
                'client_name' => $rootInvoice->client->name ?? '—',
                'client_rnc' => $rootInvoice->client->rnc ?? $rootInvoice->client->cedula ?? null,
                'subtotal' => (float)$rootInvoice->subtotal,
                'tax_amount' => (float)$rootInvoice->tax_amount,
            ],
        ];

        // Event: DGII Acceptance / Processing
        if ($rootInvoice->is_ecf && $rootInvoice->dgii_status) {
            $isAccepted = $rootInvoice->dgii_status === 'accepted';
            $isRejected = $rootInvoice->dgii_status === 'rejected';

            $timeline[] = [
                'type' => 'dgii_status',
                'title' => $isAccepted ? 'Aprobado por DGII' : ($isRejected ? 'Rechazado por DGII' : 'Estado DGII: ' . ucfirst($rootInvoice->dgii_status)),
                'subtitle' => $rootInvoice->dgii_track_id ? "Track ID: {$rootInvoice->dgii_track_id}" : "e-NCF: {$rootInvoice->encf}",
                'timestamp' => $rootInvoice->signed_at ? $rootInvoice->signed_at->toISOString() : ($rootInvoice->created_at ? $rootInvoice->created_at->toISOString() : null),
                'badge' => $isAccepted ? 'DGII Aprobado' : ($isRejected ? 'DGII Rechazado' : 'DGII ' . ucfirst($rootInvoice->dgii_status)),
                'badge_class' => $isAccepted ? 'badge-success' : ($isRejected ? 'badge-danger' : 'badge-warning'),
                'icon' => $isAccepted ? 'shield-check' : ($isRejected ? 'shield-x' : 'shield'),
                'details' => [
                    'track_id' => $rootInvoice->dgii_track_id,
                    'security_code' => $rootInvoice->security_code,
                    'signed_at' => $rootInvoice->signed_at,
                    'error_messages' => $rootInvoice->dgii_error_messages,
                ],
            ];
        }

        // Event: Invoice Sent via Email/WhatsApp
        if ($rootInvoice->sent_at) {
            $timeline[] = [
                'type' => 'invoice_sent',
                'title' => 'Comprobante Enviado',
                'subtitle' => 'Enviado al cliente mediante ' . ($rootInvoice->sent_via ?? 'correo electrónico'),
                'timestamp' => $rootInvoice->sent_at->toISOString(),
                'badge' => 'Enviado',
                'badge_class' => 'badge-info',
                'icon' => 'send',
                'details' => [
                    'sent_via' => $rootInvoice->sent_via,
                    'sent_at' => $rootInvoice->sent_at,
                ],
            ];
        }

        // Events: Payments Received
        foreach ($payments as $payment) {
            $methodName = match(strtolower($payment->payment_method ?? '')) {
                'cash', 'efectivo' => 'Efectivo',
                'card', 'tarjeta' => 'Tarjeta Débito/Crédito',
                'transfer', 'transferencia' => 'Transferencia Bancaria',
                'cheque', 'check' => 'Cheque',
                default => ucfirst($payment->payment_method ?? 'Pago'),
            };

            $timeline[] = [
                'type' => 'payment_received',
                'title' => "Pago Recibido: RD$ " . number_format((float)$payment->amount, 2),
                'subtitle' => "Método: {$methodName}" . ($payment->reference ? " · Ref: {$payment->reference}" : ''),
                'timestamp' => $payment->created_at ? $payment->created_at->toISOString() : ($payment->payment_date ? $payment->payment_date->toISOString() : null),
                'date_formatted' => $payment->payment_date ? $payment->payment_date->format('d/m/Y') : null,
                'badge' => 'Abono / Pago',
                'badge_class' => 'badge-success',
                'icon' => 'credit-card',
                'amount' => (float)$payment->amount,
                'currency' => $rootInvoice->currency ?? 'DOP',
                'details' => [
                    'payment_id' => $payment->id,
                    'payment_method' => $payment->payment_method,
                    'reference' => $payment->reference,
                    'notes' => $payment->notes,
                ],
            ];
        }

        // Events: Modifying Invoices (Notas de Crédito / Débito)
        foreach ($modifyingInvoices as $mod) {
            $isCredit = $mod->isCreditNote() || (int)$mod->ecf_type === 34;
            $typeLabel = $isCredit ? 'Nota de Crédito' : 'Nota de Débito';
            $docNumber = $mod->encf ?: $mod->invoice_number;
            $modReason = $mod->modification_reason ?: 'Sin detalle especificado';
            $modCodeDesc = self::MODIFICATION_CODES[(int)$mod->modification_code] ?? 'Modificación de comprobante';

            $timeline[] = [
                'type' => $isCredit ? 'credit_note_issued' : 'debit_note_issued',
                'title' => "{$typeLabel} Emitida ({$docNumber})",
                'subtitle' => "Motivo DGII: {$modCodeDesc} · \"{$modReason}\"",
                'timestamp' => $mod->created_at ? $mod->created_at->toISOString() : null,
                'date_formatted' => $mod->issue_date ? $mod->issue_date->format('d/m/Y') : null,
                'badge' => $typeLabel,
                'badge_class' => $isCredit ? 'badge-danger' : 'badge-warning',
                'icon' => $isCredit ? 'corner-down-left' : 'corner-up-right',
                'amount' => (float)$mod->total,
                'currency' => $mod->currency ?? 'DOP',
                'details' => [
                    'invoice_id' => $mod->id,
                    'encf' => $mod->encf,
                    'invoice_number' => $mod->invoice_number,
                    'modification_code' => $mod->modification_code,
                    'modification_code_text' => $modCodeDesc,
                    'modification_reason' => $mod->modification_reason,
                    'dgii_status' => $mod->dgii_status,
                    'dgii_track_id' => $mod->dgii_track_id,
                ],
            ];
        }

        // Event: Cancelled
        if ($isCancelled) {
            $anulationReasonDesc = self::ANULATION_TYPES[$rootInvoice->anulation_type] ?? $rootInvoice->cancellation_reason ?? 'Anulación directa';

            $timeline[] = [
                'type' => 'invoice_cancelled',
                'title' => 'Factura Anulada',
                'subtitle' => "Motivo: {$anulationReasonDesc}" . ($rootInvoice->cancellation_reason ? " (\"{$rootInvoice->cancellation_reason}\")" : ''),
                'timestamp' => $rootInvoice->cancelled_at ? $rootInvoice->cancelled_at->toISOString() : ($rootInvoice->updated_at ? $rootInvoice->updated_at->toISOString() : null),
                'badge' => 'Anulada',
                'badge_class' => 'badge-danger',
                'icon' => 'x-circle',
                'details' => [
                    'anulation_type' => $rootInvoice->anulation_type,
                    'cancellation_reason' => $rootInvoice->cancellation_reason,
                    'cancelled_at' => $rootInvoice->cancelled_at,
                ],
            ];
        }

        // Sort timeline chronologically
        usort($timeline, function ($a, $b) {
            $tA = $a['timestamp'] ?? '1970-01-01';
            $tB = $b['timestamp'] ?? '1970-01-01';
            return strcmp($tA, $tB);
        });

        // Prepare enriched modifying docs for the tree view
        $modifyingDocsFormatted = $modifyingInvoices->map(function ($m) {
            $isCredit = $m->isCreditNote() || (int)$m->ecf_type === 34;
            return [
                'id' => $m->id,
                'invoice_number' => $m->invoice_number,
                'encf' => $m->encf,
                'is_credit_note' => $isCredit,
                'is_debit_note' => (int)$m->ecf_type === 33,
                'type_label' => $isCredit ? 'Nota de Crédito' : 'Nota de Débito',
                'issue_date' => $m->issue_date ? $m->issue_date->format('Y-m-d') : null,
                'total' => (float)$m->total,
                'currency' => $m->currency ?? 'DOP',
                'modification_code' => $m->modification_code,
                'modification_code_desc' => self::MODIFICATION_CODES[(int)$m->modification_code] ?? 'Modificación de comprobante',
                'modification_reason' => $m->modification_reason,
                'dgii_status' => $m->dgii_status,
                'dgii_track_id' => $m->dgii_track_id,
            ];
        });

        return response()->json([
            'success' => true,
            'searched_doc_id' => $searchedDoc->id,
            'is_child_doc' => $isChildDoc,
            'root_invoice' => [
                'id' => $rootInvoice->id,
                'invoice_number' => $rootInvoice->invoice_number,
                'encf' => $rootInvoice->encf,
                'is_ecf' => (bool)$rootInvoice->is_ecf,
                'ecf_type' => $rootInvoice->ecf_type,
                'status' => $rootInvoice->status,
                'issue_date' => $rootInvoice->issue_date ? $rootInvoice->issue_date->format('Y-m-d') : null,
                'due_date' => $rootInvoice->due_date ? $rootInvoice->due_date->format('Y-m-d') : null,
                'subtotal' => (float)$rootInvoice->subtotal,
                'tax_amount' => (float)$rootInvoice->tax_amount,
                'total' => (float)$rootInvoice->total,
                'currency' => $rootInvoice->currency ?? 'DOP',
                'client' => $rootInvoice->client ? [
                    'id' => $rootInvoice->client->id,
                    'name' => $rootInvoice->client->name,
                    'rnc' => $rootInvoice->client->rnc ?? $rootInvoice->client->cedula ?? null,
                    'email' => $rootInvoice->client->email,
                    'phone' => $rootInvoice->client->phone,
                ] : null,
                'dgii_status' => $rootInvoice->dgii_status,
                'dgii_track_id' => $rootInvoice->dgii_track_id,
                'security_code' => $rootInvoice->security_code,
                'anulation_type' => $rootInvoice->anulation_type,
                'cancellation_reason' => $rootInvoice->cancellation_reason,
            ],
            'origin_quote' => $originQuote ? [
                'id' => $originQuote->id,
                'quote_number' => $originQuote->quote_number,
                'total' => (float)$originQuote->total,
                'currency' => $originQuote->currency ?? 'DOP',
                'issue_date' => $originQuote->issue_date ? $originQuote->issue_date->format('Y-m-d') : null,
                'status' => $originQuote->status,
            ] : null,
            'modifying_invoices' => $modifyingDocsFormatted,
            'payments' => $payments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'amount' => (float)$p->amount,
                    'payment_method' => $p->payment_method,
                    'payment_date' => $p->payment_date ? $p->payment_date->format('Y-m-d') : null,
                    'reference' => $p->reference,
                    'notes' => $p->notes,
                ];
            }),
            'financial_summary' => [
                'original_total' => $originalTotal,
                'credit_notes_total' => $creditNotesTotal,
                'debit_notes_total' => $debitNotesTotal,
                'effective_invoiced_total' => $effectiveInvoicedTotal,
                'payments_total' => $paymentsTotal,
                'net_balance' => $netBalance,
                'financial_status' => $financialStatus,
                'financial_status_label' => $financialStatusLabel,
                'is_fully_credited' => $isFullyCredited,
                'is_cancelled' => $isCancelled,
            ],
            'timeline' => $timeline,
        ]);
    }

    /**
     * Search vouchers for autocomplete / instant search bar.
     */
    public function search(Request $request)
    {
        $q = trim((string)$request->input('q', ''));
        if (strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $invoices = Invoice::with('client')
            ->where(function ($query) use ($q) {
                $query->where('invoice_number', 'like', "%{$q}%")
                    ->orWhere('encf', 'like', "%{$q}%")
                    ->orWhere('modified_ncf', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($clientQuery) use ($q) {
                        $clientQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('rnc', 'like', "%{$q}%")
                            ->orWhere('cedula', 'like', "%{$q}%");
                    });
            })
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $results = $invoices->map(function ($inv) {
            $isCredit = $inv->isCreditNote() || (int)$inv->ecf_type === 34;
            $isDebit = (int)$inv->ecf_type === 33;
            $typeLabel = $isCredit ? 'Nota de Crédito' : ($isDebit ? 'Nota de Débito' : ($inv->is_ecf ? 'e-CF' : 'Factura'));

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'encf' => $inv->encf,
                'type_label' => $typeLabel,
                'client_name' => $inv->client->name ?? 'Consumidor Final',
                'client_rnc' => $inv->client->rnc ?? $inv->client->cedula ?? null,
                'total' => (float)$inv->total,
                'currency' => $inv->currency ?? 'DOP',
                'issue_date' => $inv->issue_date ? $inv->issue_date->format('d/m/Y') : null,
                'status' => $inv->status,
                'dgii_status' => $inv->dgii_status,
                'modified_ncf' => $inv->modified_ncf,
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Get recent vouchers that have interesting tracking data (credit notes, payments, cancelled).
     */
    public function recent()
    {
        // 1. Invoices that have modifying documents
        $modifiedNcfs = Invoice::whereNotNull('modified_ncf')
            ->where('modified_ncf', '!=', '')
            ->pluck('modified_ncf')
            ->unique()
            ->take(10)
            ->toArray();

        $recentLineageInvoices = Invoice::with('client')
            ->whereIn('encf', $modifiedNcfs)
            ->orWhereIn('invoice_number', $modifiedNcfs)
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // 2. Also get latest 6 invoices generally
        $latestInvoices = Invoice::with('client')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $combined = $recentLineageInvoices->merge($latestInvoices)->unique('id')->take(8);

        return response()->json([
            'recent' => $combined->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'encf' => $inv->encf,
                    'client_name' => $inv->client->name ?? 'Consumidor Final',
                    'total' => (float)$inv->total,
                    'currency' => $inv->currency ?? 'DOP',
                    'issue_date' => $inv->issue_date ? $inv->issue_date->format('d/m/Y') : null,
                    'status' => $inv->status,
                    'dgii_status' => $inv->dgii_status,
                    'is_ecf' => (bool)$inv->is_ecf,
                ];
            })
        ]);
    }
}
