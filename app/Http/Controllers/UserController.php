<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users', description: 'Gestion des clients (Admin uniquement)')]

class UserController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/clients',
        summary: 'Lister tous les clients',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des clients retournée avec succès')
        ]
    )]
    public function index()
    {
        $clientRole = Role::where('name', 'client')->first();

        $clients = User::where('role_id', $clientRole->id)
            ->select('id', 'fullname', 'email', 'phone', 'address', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success'      => true,
            'data'         => $clients->items(),
            'current_page' => $clients->currentPage(),
            'last_page'    => $clients->lastPage(),
            'per_page'     => $clients->perPage(),
            'total'        => $clients->total(),
        ], 200);
    }

    #[OA\Get(
        path: '/api/v1/admin/clients/{id}',
        summary: 'Afficher le détail d\'un client avec ses commandes',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Client trouvé'),
            new OA\Response(response: 404, description: 'Client non trouvé')
        ]
    )]
    public function show($id)
    {
        $clientRole = Role::where('name', 'client')->first();

        $client = User::where('id', $id)
            ->where('role_id', $clientRole->id)
            ->select('id', 'fullname', 'email', 'phone', 'address', 'created_at')
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé',
            ], 404);
        }

        // Commandes du client
        $orders = $client->orders()->with('items.product', 'payment')->get();

        // Total dépensé (uniquement les commandes non annulées)
        $totalDepense = $client->orders()
            ->where('status', '!=', 'annulée')
            ->sum('total_amount');

        return response()->json([
            'success' => true,
            'data'    => [
                'client'        => $client,
                'total_depense' => number_format($totalDepense, 2),
                'nb_commandes'  => $orders->count(),
                'commandes'     => $orders,
            ],
        ], 200);
    }

    #[OA\Put(
    path: '/api/v1/admin/clients/{id}/block',
    summary: 'Bloquer un client (Admin)',
    security: [['sanctum' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Client bloqué avec succès'),
        new OA\Response(response: 404, description: 'Client non trouvé')
    ]
)]
public function block($id)
{
    $clientRole = Role::where('name', 'client')->first();

    $client = User::where('id', $id)
        ->where('role_id', $clientRole->id)
        ->first();

    if (!$client) {
        return response()->json([
            'success' => false,
            'message' => 'Client non trouvé',
        ], 404);
    }

    $client->update(['is_active' => false]);

    return response()->json([
        'success' => true,
        'message' => 'Client bloqué avec succès',
    ], 200);
}

#[OA\Put(
    path: '/api/v1/admin/clients/{id}/unblock',
    summary: 'Débloquer un client (Admin)',
    security: [['sanctum' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Client débloqué avec succès'),
        new OA\Response(response: 404, description: 'Client non trouvé')
    ]
)]
public function unblock($id)
{
    $clientRole = Role::where('name', 'client')->first();

    $client = User::where('id', $id)
        ->where('role_id', $clientRole->id)
        ->first();

    if (!$client) {
        return response()->json([
            'success' => false,
            'message' => 'Client non trouvé',
        ], 404);
    }

    $client->update(['is_active' => true]);

    return response()->json([
        'success' => true,
        'message' => 'Client débloqué avec succès',
    ], 200);
}
}