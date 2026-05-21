<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class DgiiTestUIController extends Controller
{
    /**
     * Executes the DGII certification test suite via Artisan
     * and returns the console output.
     */
    public function runTests(Request $request)
    {
        $output = new BufferedOutput();
        
        try {
            // Run the artisan command and capture output
            $exitCode = Artisan::call('dgii:run-tests', [], $output);
            
            $textOutput = $output->fetch();
            
            return response()->json([
                'success' => $exitCode === 0,
                'output' => $textOutput,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'output' => $output->fetch() . "\nError Fatal: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Downloads the FC<250k signed XMLs as a ZIP file for portal upload.
     */
    public function downloadFc250k()
    {
        $dir = storage_path('app/dgii_tests/fc_250k_upload');
        
        if (!is_dir($dir) || count(glob("$dir/*.xml")) === 0) {
            return response()->json(['error' => 'No hay archivos FC<250k. Ejecuta las pruebas primero.'], 404);
        }

        $zipPath = storage_path('app/dgii_tests/fc_250k_upload.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        foreach (glob("$dir/*.xml") as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        return response()->download($zipPath, 'fc_250k_facturas_consumo.zip')->deleteFileAfterSend(true);
    }
}
