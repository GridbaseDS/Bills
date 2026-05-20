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
}
