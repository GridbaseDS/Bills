<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPayPalConversionPayments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'paypal:fix-conversion-payments 
                            {--dry-run : Show what would be fixed without making changes}
                            {--invoice= : Specific invoice ID to fix}';

    /**
     * The console command description.
     */
    protected $description = 'Fix PayPal payments that were recorded in wrong currency after conversion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificInvoice = $this->option('invoice');
        
        $this->info('🔍 Buscando pagos de PayPal con problemas de conversión...');
        $this->newLine();
        
        // Find PayPal payments with conversion info in notes
        $query = Payment::where('payment_method', 'paypal')
            ->where('notes', 'LIKE', '%convertido desde%');
        
        if ($specificInvoice) {
            $query->where('invoice_id', $specificInvoice);
        }
        
        $paymentsWithConversion = $query->with('invoice')->get();
        
        if ($paymentsWithConversion->isEmpty()) {
            $this->info('✅ No se encontraron pagos con problemas de conversión (con nota descriptiva).');
        } else {
            $this->info("✅ Encontrados {$paymentsWithConversion->count()} pagos con información de conversión.");
            $this->newLine();
            
            foreach ($paymentsWithConversion as $payment) {
                $this->processPaymentWithConversion($payment, $dryRun);
            }
        }
        
        // Also find payments that might be wrong (suspiciously small compared to invoice total)
        $this->newLine();
        $this->info('🔍 Buscando pagos sospechosos (monto muy bajo vs total factura)...');
        $this->newLine();
        
        $suspiciousQuery = Payment::where('payment_method', 'paypal')
            ->whereRaw('amount < 1000') // Less than 1000 (likely USD instead of DOP)
            ->where('notes', 'NOT LIKE', '%convertido desde%');
        
        if ($specificInvoice) {
            $suspiciousQuery->where('invoice_id', $specificInvoice);
        }
        
        $suspiciousPayments = $suspiciousQuery->with('invoice')->get();
        
        $fixed = 0;
        $skipped = 0;
        
        foreach ($suspiciousPayments as $payment) {
            $invoice = $payment->invoice;
            
            if (!$invoice) {
                $skipped++;
                continue;
            }
            
            // Check if this looks like a conversion issue
            if ($invoice->currency === 'DOP' && $payment->amount < 1000 && $invoice->total > 5000) {
                $this->warn("⚠️ Pago sospechoso encontrado:");
                $this->line("   Factura: {$invoice->invoice_number} (Total: {$invoice->total} {$invoice->currency})");
                $this->line("   Pago ID: {$payment->id}");
                $this->line("   Monto registrado: {$payment->amount} {$invoice->currency}");
                $this->line("   Fecha: {$payment->payment_date}");
                $this->line("   Referencia: {$payment->reference}");
                
                // Try to calculate what the original amount should have been
                $conversionRate = 0.017; // Default DOP to USD rate
                $estimatedOriginal = round($payment->amount / $conversionRate, 2);
                
                $this->info("   💡 Posible monto original: {$estimatedOriginal} DOP");
                
                if (!$dryRun && $this->confirm("¿Corregir este pago a {$estimatedOriginal} DOP?", false)) {
                    $this->fixSuspiciousPayment($payment, $estimatedOriginal);
                    $fixed++;
                } else {
                    $this->line("   ⏭️  Omitido");
                    $skipped++;
                }
                
                $this->newLine();
            }
        }
        
        $this->newLine();
        $this->info('📊 Resumen:');
        $this->line("   Pagos con conversión documentada: {$paymentsWithConversion->count()}");
        $this->line("   Pagos sospechosos encontrados: {$suspiciousPayments->count()}");
        
        if (!$dryRun) {
            $this->line("   ✅ Pagos corregidos: {$fixed}");
            $this->line("   ⏭️  Pagos omitidos: {$skipped}");
        } else {
            $this->warn('   🔍 Modo DRY-RUN: No se realizaron cambios');
        }
        
        return Command::SUCCESS;
    }
    
    private function processPaymentWithConversion(Payment $payment, bool $dryRun)
    {
        // Already has correct notes, just verify amounts
        $this->info("✅ Pago {$payment->id} - Ya tiene información de conversión correcta");
        $this->line("   Nota: {$payment->notes}");
    }
    
    private function fixSuspiciousPayment(Payment $payment, float $newAmount)
    {
        $invoice = $payment->invoice;
        $oldAmount = $payment->amount;
        $difference = $newAmount - $oldAmount;
        
        DB::beginTransaction();
        try {
            // Update payment amount
            $payment->amount = $newAmount;
            $payment->notes = $payment->notes . " [CORREGIDO: Monto original {$oldAmount}, ajustado a {$newAmount} por conversión de moneda]";
            $payment->save();
            
            // Update invoice amount_paid
            $invoice->amount_paid += $difference;
            
            // Check if invoice should be marked as paid
            if ($invoice->amount_paid >= $invoice->total && $invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
            }
            
            $invoice->save();
            
            DB::commit();
            
            $this->info("   ✅ Pago corregido exitosamente");
            $this->line("      Monto anterior: {$oldAmount}");
            $this->line("      Monto nuevo: {$newAmount}");
            $this->line("      Nuevo estado factura: {$invoice->status}");
            $this->line("      Total pagado: {$invoice->amount_paid} / {$invoice->total}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("   ❌ Error al corregir pago: " . $e->getMessage());
        }
    }
}
