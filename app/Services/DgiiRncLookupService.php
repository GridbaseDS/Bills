<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DgiiRncLookupService
{
    /**
     * The base URL for the unofficial DGII API.
     * Note: In production, consider using a paid/official service if reliability is a concern.
     */
    protected string $baseUrl = 'https://api.digital.gob.do/v1/bce/rnc/';

    /**
     * Looks up an RNC (Registry Number) or Cédula to get the associated business/person name.
     * Uses the official DGII web consultation page acting as a web scraper.
     */
    public function lookup(string $rnc): ?array
    {
        $cleanRnc = preg_replace('/[^0-9]/', '', $rnc);

        if (empty($cleanRnc) || (strlen($cleanRnc) !== 9 && strlen($cleanRnc) !== 11)) {
            return null; 
        }

        $cacheKey = "dgii_scraper_{$cleanRnc}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $url = 'https://dgii.gov.do/app/WebApps/ConsultasWeb2/ConsultasWeb/consultas/rnc.aspx';
            $cookieFile = storage_path('app/dgii_cookies.txt');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            $htmlGet = curl_exec($ch);

            if (!$htmlGet) {
                curl_close($ch);
                return null;
            }

            $viewstate = $this->extractField($htmlGet, '__VIEWSTATE');
            $viewstateGen = $this->extractField($htmlGet, '__VIEWSTATEGENERATOR');
            $eventValidation = $this->extractField($htmlGet, '__EVENTVALIDATION');

            $postData = http_build_query([
                '__EVENTTARGET' => '',
                '__EVENTARGUMENT' => '',
                '__VIEWSTATE' => $viewstate,
                '__VIEWSTATEGENERATOR' => $viewstateGen,
                '__EVENTVALIDATION' => $eventValidation,
                'ctl00$cphMain$txtRNCCedula' => $cleanRnc,
                'ctl00$cphMain$btnBuscarPorRNC' => 'BUSCAR'
            ]);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $htmlPost = curl_exec($ch);
            curl_close($ch);

            if ($htmlPost) {
                // Extract Razón Social using regex
                if (preg_match('/Nombre\/Raz\&#243;n Social.*?<\/td>\s*<td>([^<]+)<\/td>/is', $htmlPost, $matches)) {
                    $nombre = trim($matches[1]);
                    
                    $result = [
                        'nombre' => mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8"),
                        'tipo' => strlen($cleanRnc) === 9 ? 'Juridica' : 'Fisica',
                        'rnc' => $cleanRnc,
                        'status' => 'ACTIVO' // Assuming active if it shows up, or could parse "Estado"
                    ];

                    Cache::put($cacheKey, $result, now()->addHours(24));
                    return $result;
                }
            }

            Log::info("[DGII Service] RNC/Cédula no encontrado en la página oficial DGII: {$cleanRnc}");

        } catch (\Exception $e) {
            Log::error("[DGII Service] Error conectando a DGII Oficial para {$cleanRnc}: " . $e->getMessage());
        }

        return null;
    }

    private function extractField($html, $id) {
        if (preg_match('/<input type="hidden" name="' . preg_quote($id) . '" id="' . preg_quote($id) . '" value="([^"]*)"/', $html, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
