<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Client::orderBy('company_name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:200',
            'contact_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'tax_id' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        $client = Client::create($validated);
        return response()->json(['success' => true, 'client' => $client], 201);
    }

    public function show($id)
    {
        $client = Client::findOrFail($id);
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $client->update($request->all());
        return response()->json(['success' => true, 'client' => $client]);
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        if ($client->invoices()->exists() || $client->quotes()->exists()) {
            return response()->json(['error' => 'Cannot delete client with existing records'], 400);
        }
        $client->delete();
        return response()->json(['success' => true]);
    }
}
