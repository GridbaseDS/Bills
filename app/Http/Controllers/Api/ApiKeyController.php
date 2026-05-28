<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Log;

class ApiKeyController extends Controller
{
    /**
     * List all API keys.
     */
    public function index()
    {
        $keys = ApiKey::with('creator:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'prefix' => $key->plain_key_prefix,
                    'token' => $key->plain_key,
                    'permissions' => $key->permissions ?? [],
                    'rate_limit' => $key->rate_limit,
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'expires_at' => $key->expires_at?->toIso8601String(),
                    'created_by' => $key->creator?->name ?? 'Sistema',
                    'created_at' => $key->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $keys,
            'available_permissions' => ApiKey::AVAILABLE_PERMISSIONS,
        ]);
    }

    /**
     * Create a new API key.
     * Returns the plain text token ONCE — it cannot be retrieved again.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', ApiKey::AVAILABLE_PERMISSIONS) . ',*',
            'rate_limit' => 'sometimes|integer|min:1|max:1000',
            'expires_at' => 'sometimes|nullable|date|after:now',
        ]);

        $token = ApiKey::generateToken();

        $apiKey = ApiKey::create([
            'name' => $request->name,
            'key' => $token['hash'],
            'plain_key' => $token['plain'],
            'plain_key_prefix' => $token['prefix'],
            'permissions' => $request->permissions,
            'rate_limit' => $request->rate_limit ?? 60,
            'is_active' => true,
            'expires_at' => $request->expires_at,
            'created_by' => $request->user()->id,
        ]);

        Log::info("API Key created: '{$apiKey->name}' (ID: {$apiKey->id}) by user {$request->user()->email}");

        return response()->json([
            'success' => true,
            'message' => 'API Key creada exitosamente. Guarda el token, no se mostrará de nuevo.',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'token' => $token['plain'],  // ⚠️ Only returned ONCE
                'prefix' => $apiKey->plain_key_prefix,
                'permissions' => $apiKey->permissions,
                'rate_limit' => $apiKey->rate_limit,
                'expires_at' => $apiKey->expires_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show a single API key (without the token).
     */
    public function show($id)
    {
        $key = ApiKey::with('creator:id,name,email')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $key->id,
                'name' => $key->name,
                'prefix' => $key->plain_key_prefix,
                'permissions' => $key->permissions ?? [],
                'rate_limit' => $key->rate_limit,
                'is_active' => $key->is_active,
                'last_used_at' => $key->last_used_at?->toIso8601String(),
                'expires_at' => $key->expires_at?->toIso8601String(),
                'created_by' => $key->creator?->name ?? 'Sistema',
                'created_at' => $key->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update an API key (name, permissions, rate_limit, is_active, expires_at).
     */
    public function update(Request $request, $id)
    {
        $apiKey = ApiKey::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', ApiKey::AVAILABLE_PERMISSIONS) . ',*',
            'rate_limit' => 'sometimes|integer|min:1|max:1000',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'sometimes|nullable|date',
        ]);

        $apiKey->update($request->only([
            'name', 'permissions', 'rate_limit', 'is_active', 'expires_at',
        ]));

        Log::info("API Key updated: '{$apiKey->name}' (ID: {$apiKey->id}) by user {$request->user()->email}");

        return response()->json([
            'success' => true,
            'message' => 'API Key actualizada.',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'prefix' => $apiKey->plain_key_prefix,
                'permissions' => $apiKey->permissions,
                'rate_limit' => $apiKey->rate_limit,
                'is_active' => $apiKey->is_active,
                'expires_at' => $apiKey->expires_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Revoke (delete) an API key.
     */
    public function destroy(Request $request, $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $name = $apiKey->name;
        $apiKey->delete();

        Log::info("API Key revoked: '{$name}' (ID: {$id}) by user {$request->user()->email}");

        return response()->json([
            'success' => true,
            'message' => "API Key '{$name}' revocada exitosamente.",
        ]);
    }

    /**
     * Regenerate a token for an existing API key.
     * Returns the new plain text token ONCE.
     */
    public function regenerate(Request $request, $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $token = ApiKey::generateToken();

        $apiKey->update([
            'key' => $token['hash'],
            'plain_key' => $token['plain'],
            'plain_key_prefix' => $token['prefix'],
        ]);

        Log::info("API Key regenerated: '{$apiKey->name}' (ID: {$apiKey->id}) by user {$request->user()->email}");

        return response()->json([
            'success' => true,
            'message' => 'Token regenerado. Guarda el nuevo token, no se mostrará de nuevo.',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'token' => $token['plain'],  // ⚠️ Only returned ONCE
                'prefix' => $apiKey->plain_key_prefix,
            ],
        ]);
    }
}
