<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;


class AuthController extends Controller
{
    // Connexion (personnel BMI + clients)
    #[OA\Post(
        path: '/api/v1/login',
        summary: 'Connexion utilisateur',
        description: 'Connexion pour le personnel BMI et les clients',
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@bmi.bj'),
                    new OA\Property(property: 'password', type: 'string', example: 'password_secret')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Connexion réussie'),
            new OA\Response(response: 401, description: 'Email ou mot de passe incorrect'),
            new OA\Response(response: 422, description: 'Données invalides')
        ]
    )]
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

    // Inscription (clients uniquement via Flutter)
    #[OA\Post(
        path: '/api/v1/register',
        summary: 'Inscription client',
        description: 'Inscription réservée aux clients via Flutter. Le rôle client est forcé côté serveur.',
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fullname', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'fullname', type: 'string', example: 'Client Test'),
                    new OA\Property(property: 'email', type: 'string', example: 'client@test.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    new OA\Property(property: 'phone', type: 'string', example: '97000000'),
                    new OA\Property(property: 'address', type: 'string', example: 'Cotonou, Bénin')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Inscription réussie'),
            new OA\Response(response: 422, description: 'Données invalides')
        ]
    )]
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

    // Création du personnel par l'Admin (via React)
    #[OA\Post(
        path: '/api/v1/admin/users',
        summary: 'Créer un compte personnel',
        description: 'Création d\'un technicien ou gestionnaire par l\'admin uniquement',
        security: [['sanctum' => []]],
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fullname', 'email', 'password', 'role'],
                properties: [
                    new OA\Property(property: 'fullname', type: 'string', example: 'Jean Technicien'),
                    new OA\Property(property: 'email', type: 'string', example: 'jean@bmi.bj'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    new OA\Property(property: 'phone', type: 'string', example: '97000000'),
                    new OA\Property(property: 'role', type: 'string', enum: ['technicien', 'gestionnaire'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé avec succès'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé — admin uniquement'),
            new OA\Response(response: 422, description: 'Données invalides')
        ]
    )]
    public function createStaff(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string',
            'role'     => 'required|in:technicien,gestionnaire',
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

    // Déconnexion
    #[OA\Post(
        path: '/api/v1/logout',
        summary: 'Déconnexion',
        security: [['sanctum' => []]],
        tags: ['Authentification'],
        responses: [
            new OA\Response(response: 200, description: 'Déconnexion réussie'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ], 200);
    }

    // Profil utilisateur connecté
    #[OA\Get(
        path: '/api/v1/me',
        summary: 'Profil utilisateur connecté',
        security: [['sanctum' => []]],
        tags: ['Authentification'],
        responses: [
            new OA\Response(response: 200, description: 'Profil retourné avec succès'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
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