<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    // Connexion (personnel BMI + clients) 
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        // Supprime les anciens tokens pour éviter l'accumulation
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'token'   => $token,
            'user'    => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'address'  => $user->address,
                'role'     => $user->role->name,
            ]
        ], 200);
    }

    //  Inscription (clients uniquement via Flutter) 
    public function register(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string',
            'address'  => 'nullable|string',
        ]);

        // Rôle client forcé côté serveur , jamais depuis la requête
        $clientRole = Role::where('name', 'client')->first();

        $user = User::create([
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'address'  => $request->address,
            'role_id'  => $clientRole->id,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'token'   => $token,
            'user'    => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'address'  => $user->address,
                'role'     => 'client',
            ]
        ], 201);
    }

    // Création du personnnel par l'Admin (via React) 
    public function createStaff(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string',
            'role'     => 'required|in:technicien,gestionnaire', // admin ne peut pas créer un autre admin
        ]);

        $role = Role::where('name', $request->role)->first();

        $user = User::create([
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'address'  => null,
            'role_id'  => $role->id,
        ]);

        return response()->json([
            'message' => 'Compte créé avec succès',
            'user'    => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'role'     => $role->name,
            ]
        ], 201);
    }

    //  Déconnexion 
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ], 200);
    }

    //  Profil utilisateur connecté
    public function me(Request $request)
    {
        $user = $request->user()->load('role');

        return response()->json([
            'user' => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'address'  => $user->address,
                'role'     => $user->role->name,
            ]
        ], 200);
    }
}