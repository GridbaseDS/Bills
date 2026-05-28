<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;

class ExternalClientController extends Controller
{
    /**
     * List clients (paginated).
     *
     * GET /api/v1/clients?page=1&per_page=20&search=company
     */
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tax_id')) {
            $query->where('tax_id', $request->tax_id);
        }

        $perPage = min((int) ($request->per_page ?? 20), 100);
        $clients = $query->orderBy('company_name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $clients->items(),
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
        ]);
    }

    /**
     * Show a single client.
     */
    public function show($id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'error' => 'Cliente no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $client,
        ]);
    }

    /**
     * Create a client (or return existing if tax_id/email match).
     *
     * POST /api/v1/clients
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'sometimes|string|max:255',
            'contact_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:30',
            'whatsapp' => 'sometimes|string|max:30',
            'tax_id' => 'sometimes|string|max:20',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de validación inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // At least one name field required
        if (empty($request->company_name) && empty($request->contact_name)) {
            return response()->json([
                'success' => false,
                'error' => 'Se requiere al menos company_name o contact_name.',
            ], 422);
        }

        // Upsert: check if client already exists by tax_id or email
        $existing = null;
        if ($request->filled('tax_id')) {
            $existing = Client::where('tax_id', $request->tax_id)->first();
        }
        if (!$existing && $request->filled('email')) {
            $existing = Client::where('email', $request->email)->first();
        }

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Cliente existente encontrado.',
                'is_new' => false,
                'data' => $existing,
            ]);
        }

        $client = Client::create([
            'company_name' => $request->company_name,
            'contact_name' => $request->contact_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'tax_id' => $request->tax_id,
            'address_line1' => $request->address_line1,
            'address_line2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country ?? 'DO',
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado exitosamente.',
            'is_new' => true,
            'data' => $client,
        ], 201);
    }
}
