<?php

namespace App\Http\Controllers;

use App\Models\Breakdown;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pannes', description: 'Gestion des pannes des équipements')]
class BreakdownController extends Controller
{
    #[OA\Get(
        path: '/api/v1/breakdowns',
        summary: 'Liste toutes les pannes',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        responses: [
            new OA\Response(response: 200, description: 'Liste retournée avec succès'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function index()
    {
        $breakdowns = Breakdown::with(['equipment', 'declaredBy'])->get();
        return response()->json($breakdowns, 200);
    }

    #[OA\Post(
        path: '/api/v1/breakdowns',
        summary: 'Signaler une panne',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['equipment_id', 'user_id', 'description', 'priority', 'reported_at'],
                properties: [
                    new OA\Property(property: 'equipment_id', type: 'integer', example: 1),
                    new OA\Property(property: 'user_id', type: 'integer', example: 2),
                    new OA\Property(property: 'description', type: 'string', example: 'Vibrations anormales'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['faible', 'moyenne', 'critique']),
                    new OA\Property(property: 'status', type: 'string', enum: ['ouverte', 'en_cours', 'resolue']),
                    new OA\Property(property: 'reported_at', type: 'string', format: 'date-time', example: '2026-02-19 10:00:00'),
                    new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', example: null)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Panne signalée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — admin ou technicien uniquement'),
            new OA\Response(response: 422, description: 'Données invalides')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'user_id'      => 'required|exists:users,id',
            'description'  => 'required|string',
            'priority'     => 'required|in:faible,moyenne,critique',
            'status'       => 'nullable|in:ouverte,en_cours,resolue',
            'reported_at'  => 'required|date',
            'resolved_at'  => 'nullable|date|after:reported_at',
        ]);
        $breakdown = Breakdown::create($request->all());
        return response()->json([
            'message'   => 'Panne signalée avec succès',
            'breakdown' => $breakdown->load(['equipment', 'declaredBy'])
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/breakdowns/{id}',
        summary: 'Détail d\'une panne',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Panne retournée avec succès'),
            new OA\Response(response: 404, description: 'Panne non trouvée')
        ]
    )]
    public function show($id)
    {
        $breakdown = Breakdown::with(['equipment', 'declaredBy'])->find($id);
        if (!$breakdown) {
            return response()->json(['message' => 'Panne non trouvée'], 404);
        }
        return response()->json($breakdown, 200);
    }

    #[OA\Put(
        path: '/api/v1/breakdowns/{id}',
        summary: 'Modifier une panne',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['ouverte', 'en_cours', 'resolue']),
                    new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', example: '2026-02-20 15:00:00')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Panne modifiée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Panne non trouvée')
        ]
    )]
    public function update(Request $request, $id)
    {
        $breakdown = Breakdown::find($id);
        if (!$breakdown) {
            return response()->json(['message' => 'Panne non trouvée'], 404);
        }
        $request->validate([
            'equipment_id' => 'nullable|exists:equipments,id',
            'user_id'      => 'nullable|exists:users,id',
            'description'  => 'nullable|string',
            'priority'     => 'nullable|in:faible,moyenne,critique',
            'status'       => 'nullable|in:ouverte,en_cours,resolue',
            'reported_at'  => 'nullable|date',
            'resolved_at'  => 'nullable|date|after:reported_at',
        ]);
        $breakdown->update($request->all());
        return response()->json([
            'message'   => 'Panne modifiée avec succès',
            'breakdown' => $breakdown->load(['equipment', 'declaredBy'])
        ], 200);
    }

    #[OA\Delete(
        path: '/api/v1/breakdowns/{id}',
        summary: 'Supprimer une panne',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Panne supprimée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — admin uniquement'),
            new OA\Response(response: 404, description: 'Panne non trouvée')
        ]
    )]
    public function destroy($id)
    {
        $breakdown = Breakdown::find($id);
        if (!$breakdown) {
            return response()->json(['message' => 'Panne non trouvée'], 404);
        }
        $breakdown->delete();
        return response()->json(['message' => 'Panne supprimée avec succès'], 200);
    }
}