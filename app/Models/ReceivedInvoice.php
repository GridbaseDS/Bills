<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivedInvoice extends Model
{
    protected $fillable = [
        'rnc_emisor',
        'razon_social_emisor',
        'encf',
        'ecf_type',
        'fecha_emision',
        'monto_total',
        'raw_xml',
        'approval_status',
        'rejection_reason',
        'approved_at',
        'acecf_sent_to_dgii',
        'acecf_sent_to_emisor',
        'dgii_acecf_response',
        'emisor_acecf_response',
        'acecf_sent_at',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'monto_total' => 'decimal:2',
        'approved_at' => 'datetime',
        'acecf_sent_at' => 'datetime',
        'acecf_sent_to_dgii' => 'boolean',
        'acecf_sent_to_emisor' => 'boolean',
    ];

    /**
     * Determine the e-CF type code from the eNCF prefix.
     */
    public static function extractEcfType(string $encf): string
    {
        // E310000000004 → E31
        if (preg_match('/^(E\d{2})/', $encf, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * Scope: pending approval
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Check if this invoice can have a commercial approval sent.
     * Types E32, E41, E43, E46, E47 do NOT require commercial approval.
     */
    public function requiresApproval(): bool
    {
        $noApprovalTypes = ['E32', 'E41', 'E43', 'E46', 'E47'];
        return !in_array($this->ecf_type, $noApprovalTypes);
    }
}
