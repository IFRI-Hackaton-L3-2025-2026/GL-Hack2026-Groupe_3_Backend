<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Maintenances', description: 'Gestion des maintenances des équipements')]
class MaintenanceController extends Controller
{
    #[OA\Get(
        path: '/api/v1/maintenances',
        summary: 'Liste toutes les maintenances',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        responses: [
            new OA\Response(response: 200, description: 'Liste retournée avec succès'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function index()
    {
        $maintenances = Maintenance::with(['equipment', 'technicien'])->get();
        return response()->json($maintenances, 200);
    }

    #[OA\Post(
        path: '/api/v1/maintenances',
        summary: 'Planifier une maintenance',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['equipment_id', 'user_id', 'type', 'start_date'],
                properties: [
                    new OA\Property(property: 'equipment_id', type: 'integer', example: 1),
                    new OA\Property(property: 'user_id', type: 'integer', example: 2),
                    new OA\Property(property: 'type', type: 'string', enum: ['preventive', 'corrective']),
                    new OA\Property(property: 'status', type: 'string', enum: ['planifiee', 'en_cours', 'terminee', 'annulee']),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time', example: '2026-02-25 08:00:00'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time', example: '2026-02-25 17:00:00'),
                    new OA\Property(property: 'cost', type: 'number', example: 50000),
                    new OA\Property(property: 'description', type: 'string', example: 'Maintenance préventive trimestrielle')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Maintenance créée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 422, description: 'Données invalides')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'user_id'      => 'required|exists:users,id',
            'type'         => 'required|in:preventive,corrective',
            'status'       => 'nullable|in:planifiee,en_cours,terminee,annulee',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after:start_date',
            'cost'         => 'nullable|numeric',
            'description'  => 'nullable|string',
        ]);

        $maintenance = Maintenance::create($request->all());

        return response()->json([
            'message'     => 'Maintenance créée avec succès',
            'maintenance' => $maintenance->load(['equipment', 'technicien'])
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/maintenances/{id}',
        summary: 'Détail d\'une maintenance',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Maintenance retournée avec succès'),
            new OA\Response(response: 404, description: 'Maintenance non trouvée')
        ]
    )]
    public function show($id)
    {
        $maintenance = Maintenance::with(['equipment', 'technicien'])->find($id);

        if (!$maintenance) {
            return response()->json(['message' => 'Maintenance non trouvée'], 404);
        }

        return response()->json($maintenance, 200);
    }

    #[OA\Put(
        path: '/api/v1/maintenances/{id}',
        summary: 'Modifier une maintenance',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['planifiee', 'en_cours', 'terminee', 'annulee']),
                    new OA\Property(property: 'cost', type: 'number', example: 75000),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time', example: '2026-02-25 17:00:00')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Maintenance modifiée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Maintenance non trouvée')
        ]
    )]
    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json(['message' => 'Maintenance non trouvée'], 404);
        }

        $request->validate([
            'equipment_id' => 'nullable|exists:equipments,id',
            'user_id'      => 'nullable|exists:users,id',
            'type'         => 'nullable|in:preventive,corrective',
            'status'       => 'nullable|in:planifiee,en_cours,terminee,annulee',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after:start_date',
            'cost'         => 'nullable|numeric',
            'description'  => 'nullable|string',
        ]);

        $maintenance->update($request->all());

        return response()->json([
            'message'     => 'Maintenance modifiée avec succès',
            'maintenance' => $maintenance->load(['equipment', 'technicien'])
        ], 200);
    }

    #[OA\Delete(
        path: '/api/v1/maintenances/{id}',
        summary: 'Supprimer une maintenance',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Maintenance supprimée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — admin uniquement'),
            new OA\Response(response: 404, description: 'Maintenance non trouvée')
        ]
    )]
    public function destroy($id)
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json(['message' => 'Maintenance non trouvée'], 404);
        }

        $maintenance->delete();

        return response()->json(['message' => 'Maintenance supprimée avec succès'], 200);
    }
}