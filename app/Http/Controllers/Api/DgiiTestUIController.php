<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\BufferedOutput;

class DgiiTestUIController extends Controller
{
    /**
     * Executes the DGII certification test suite via Artisan
     * and returns the console output + FC<250k files for download.
     */
    public function runTests(Request $request)
    {
        $output = new BufferedOutput();
        
        try {
            $exitCode = Artisan::call('dgii:run-tests', [], $output);
            $textOutput = $output->fetch();
            
            // Collect FC<250k signed files for client download
            $fc250kFiles = [];
            $dir = storage_path('app/dgii_tests/fc_250k_upload');
            if (is_dir($dir)) {
                foreach (glob("$dir/*.xml") as $file) {
                    $fc250kFiles[] = [
                        'name' => basename($file),
                        'content' => base64_encode(file_get_contents($file)),
                    ];
                }
            }
            
            return response()->json([
                'success' => $exitCode === 0,
                'output' => $textOutput,
                'fc250k_files' => $fc250kFiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'output' => $output->fetch() . "\nError Fatal: " . $e->getMessage(),
                'fc250k_files' => [],
            ], 500);
        }
    }
}
