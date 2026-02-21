<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Authentification et Génération de Token
     * * Permet à tout utilisateur (Admin, Staff ou Client) de se connecter. 
     * Le système révoque automatiquement les anciens jetons pour garantir une session unique par utilisateur.
     */
    #[OA\Post(
        path: '/api/v1/login',
        summary: 'Connexion utilisateur',
        description: 'Vérifie les identifiants et retourne un Bearer Token. Valable pour tous les types de comptes (ADMIN, GESTIONNAIRE, TECNNICIEN, CLIENTS).',
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@bmi.bj', description: 'Email de connexion'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password_secret', description: 'Mot de passe sécurisé')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Connexion réussie'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123def456'),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'fullname', type: 'string', example: 'Jean Dupont'),
                            new OA\Property(property: 'email', type: 'string', example: 'admin@bmi.bj'),
                            new OA\Property(property: 'role', type: 'string', example: 'admin')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Identifiants invalides (Email ou mot de passe incorrect)'),
            new OA\Response(response: 422, description: 'Format des données invalide (ex: email non valide)')
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

    /**
     * Inscription des Clients (Application Mobile)
     * * Cette route est exclusivement dédiée à l'enregistrement des nouveaux clients via Flutter.
     * Le système force l'attribution du rôle "client" pour éviter toute faille de privilèges.
     */
    #[OA\Post(
        path: '/api/v1/register',
        summary: 'Inscription client',
        description: 'Crée un nouveau profil utilisateur avec le rôle "client" par défaut.',
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fullname', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'fullname', type: 'string', example: 'Client Test', description: 'Nom complet du client'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'client@test.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6, example: 'password123'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '97000000'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Cotonou, Bénin')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte client créé avec succès'),
            new OA\Response(response: 422, description: 'Erreur de validation ou email déjà utilisé')
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

    /**
     * Création de Staff (Dashboard Admin)
     * * Réservé aux administrateurs pour enregistrer manuellement le personnel (Techniciens et Gestionnaires).
     */
    #[OA\Post(
        path: '/api/v1/admin/users',
        summary: 'Créer un compte personnel (Admin only)',
        description: 'Permet à un administrateur authentifié de créer un technicien ou un gestionnaire.',
        security: [['sanctum' => []]],
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fullname', 'email', 'password', 'role'],
                properties: [
                    new OA\Property(property: 'fullname', type: 'string', example: 'Jean Technicien'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jean@bmi.bj'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    new OA\Property(property: 'phone', type: 'string', example: '97000000'),
                    new OA\Property(property: 'role', type: 'string', enum: ['technicien', 'gestionnaire'], description: 'Le rôle professionnel à attribuer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte personnel créé avec succès'),
            new OA\Response(response: 401, description: 'Session non authentifiée'),
            new OA\Response(response: 403, description: 'Accès interdit - Droits administrateur requis'),
            new OA\Response(response: 422, description: 'Données invalides ou email déjà pris')
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

    /**
     * Déconnexion sécurisée
     */
    #[OA\Post(
        path: '/api/v1/logout',
        summary: 'Déconnexion',
        security: [['sanctum' => []]],
        description: 'Invalide le jeton d\'accès actuel pour déconnecter l\'utilisateur proprement.',
        tags: ['Authentification'],
        responses: [
            new OA\Response(response: 200, description: 'Session fermée avec succès'),
            new OA\Response(response: 401, description: 'Utilisateur non authentifié')
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ], 200);
    }

    /**
     * Consultation du profil actuel
     * * Retourne toutes les informations liées au compte de l'utilisateur connecté.
     */
    #[OA\Get(
        path: '/api/v1/me',
        summary: 'Profil utilisateur connecté',
        security: [['sanctum' => []]],
        description: 'Récupère les détails du compte de la session active (Id, Nom, Email, Téléphone, Adresse, Rôle).',
        tags: ['Authentification'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Profil récupéré avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'fullname', type: 'string', example: 'Jean Dupont'),
                            new OA\Property(property: 'email', type: 'string', example: 'admin@bmi.bj'),
                            new OA\Property(property: 'role', type: 'string', example: 'admin')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Authentification requise')
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