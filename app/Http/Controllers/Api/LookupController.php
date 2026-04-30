<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DgiiRncLookupService;
use App\Services\CedulaLookupService;

class LookupController extends Controller
{
    public function rnc(Request $request, $rnc, DgiiRncLookupService $service)
    {
        $result = $service->lookup($rnc);

        if ($result) {
            return response()->json([
                'found' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'found' => false,
            'message' => 'RNC o Cédula no encontrada en DGII'
        ], 404);
    }

    public function cedula(Request $request, $cedula, CedulaLookupService $service)
    {
        $result = $service->lookup($cedula);

        if ($result) {
            return response()->json([
                'found' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'found' => false,
            'message' => 'Cédula no encontrada en el padrón'
        ], 404);
    }
}
