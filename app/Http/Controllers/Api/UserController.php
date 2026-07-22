<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all users.
     */
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();
        return response()->json($users);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'gerente', 'contador', 'vendedor'])],
            'is_active' => 'boolean'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->input('is_active', true);

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente.',
            'user' => $user
        ], 201);
    }

    /**
     * Show a single user details.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'gerente', 'contador', 'vendedor'])],
            'is_active' => 'boolean'
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6';
        }

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $validated['is_active'] = $request->input('is_active', $user->is_active);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente.',
            'user' => $user
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting oneself
        if (auth()->id() == $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado.',
                'message' => 'No puedes eliminar tu propio usuario.'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente.'
        ]);
    }
}
