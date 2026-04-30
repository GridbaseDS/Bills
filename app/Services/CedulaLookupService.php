<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CedulaLookupService
{
    /**
     * Look up a Cedula to get the person's details.
     */
    public function lookup(string $cedula): ?array
    {
        $cleanCedula = preg_replace('/[^0-9]/', '', $cedula);

        if (empty($cleanCedula) || strlen($cleanCedula) !== 11) {
            return null;
        }

        $cacheKey = "cedula_api_{$cleanCedula}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->post('https://citaslanuevalicencia.lat.do/api/public/validate-cedula', [
                'cedula' => $cleanCedula
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['found']) && $data['found'] && isset($data['citizen'])) {
                    Cache::put($cacheKey, $data['citizen'], now()->addHours(24));
                    return $data['citizen'];
                }
            } else {
                Log::error("[Cedula Service] API returned error for {$cleanCedula}: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("[Cedula Service] Error conectando a API para {$cleanCedula}: " . $e->getMessage());
        }

        return null;
    }
}
