<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DgiiLog extends Model
{
    protected $fillable = [
        'invoice_id', 'encf', 'ecf_type', 'step', 'level', 'message', 'context',
        'http_method', 'http_url', 'http_status', 'http_request_body_excerpt',
        'http_response_body', 'http_duration_ms',
        'dgii_track_id', 'dgii_status', 'dgii_error_messages',
        'qr_verified', 'qr_url'
    ];

    protected $casts = [
        'context' => 'array',
        'qr_verified' => 'boolean',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Quick helper to create an info-level log entry.
     */
    public static function logStep(
        string $step,
        string $message,
        ?int $invoiceId = null,
        ?string $encf = null,
        ?string $ecfType = null,
        array $extra = []
    ): self {
        return static::create(array_merge([
            'invoice_id' => $invoiceId,
            'encf' => $encf,
            'ecf_type' => $ecfType,
            'step' => $step,
            'level' => 'info',
            'message' => $message,
        ], $extra));
    }

    /**
     * Log an HTTP request/response pair.
     */
    public static function logHttp(
        string $step,
        string $message,
        string $method,
        string $url,
        int $httpStatus,
        ?string $requestExcerpt,
        ?string $responseBody,
        float $durationMs,
        ?int $invoiceId = null,
        ?string $encf = null,
        ?string $ecfType = null,
        string $level = 'info',
        array $extra = []
    ): self {
        return static::create(array_merge([
            'invoice_id' => $invoiceId,
            'encf' => $encf,
            'ecf_type' => $ecfType,
            'step' => $step,
            'level' => $level,
            'message' => $message,
            'http_method' => $method,
            'http_url' => $url,
            'http_status' => $httpStatus,
            'http_request_body_excerpt' => $requestExcerpt ? substr($requestExcerpt, 0, 2000) : null,
            'http_response_body' => $responseBody,
            'http_duration_ms' => $durationMs,
        ], $extra));
    }
}
