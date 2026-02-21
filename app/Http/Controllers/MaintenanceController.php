<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Maintenances', description: 'Planification et suivi des interventions techniques sur le parc')]
class MaintenanceController extends Controller
{
    /**
     * Liste des interventions
     * * Récupère l'historique et le planning des maintenances incluant l'équipement concerné et le technicien assigné.
     */
    #[OA\Get(
        path: '/api/v1/maintenances',
        summary: 'Lister toutes les maintenances',
        description: 'Retourne la liste complète des interventions programmées, en cours ou terminées.',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Planning récupéré avec succès',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'type', type: 'string', example: 'preventive'),
                            new OA\Property(property: 'status', type: 'string', example: 'planifiee'),
                            new OA\Property(property: 'equipment', type: 'object', properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Groupe Électrogène B12')
                            ]),
                            new OA\Property(property: 'technicien', type: 'object', properties: [
                                new OA\Property(property: 'fullname', type: 'string', example: 'Marc Réparateur')
                            ])
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function index()
    {
        $maintenances = Maintenance::with(['equipment', 'technicien'])->get();
        return response()->json($maintenances, 200);
    }

    /**
     * Planification d'intervention
     * * Permet de réserver un créneau pour une maintenance. Le statut par défaut est "planifiee".
     */
    #[OA\Post(
        path: '/api/v1/maintenances',
        summary: 'Planifier une intervention',
        description: 'Crée un nouveau ticket de maintenance. Le coût peut être renseigné ultérieurement lors de la clôture.',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['equipment_id', 'user_id', 'type', 'start_date'],
                properties: [
                    new OA\Property(property: 'equipment_id', type: 'integer', example: 1, description: 'ID de l\'équipement'),
                    new OA\Property(property: 'user_id', type: 'integer', example: 2, description: 'ID du technicien assigné'),
                    new OA\Property(property: 'type', type: 'string', enum: ['preventive', 'corrective'], example: 'preventive'),
                    new OA\Property(property: 'status', type: 'string', enum: ['planifiee', 'en_cours', 'terminee', 'annulee'], example: 'planifiee'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time', example: '2026-02-25 08:00:00'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time', example: '2026-02-25 17:00:00', nullable: true),
                    new OA\Property(property: 'cost', type: 'number', example: 50000, description: 'Coût estimé ou réel en FCFA/EUR'),
                    new OA\Property(property: 'description', type: 'string', example: 'Remplacement des filtres et vidange')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Maintenance planifiée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 422, description: 'Erreur de validation des dates ou IDs')
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

    /**
     * Détails d'une intervention
     */
    #[OA\Get(
        path: '/api/v1/maintenances/{id}',
        summary: 'Voir le détail d\'une maintenance',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID unique', schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails récupérés'),
            new OA\Response(response: 404, description: 'Maintenance introuvable')
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

    /**
     * Mise à jour de l'intervention
     * * Utilisé pour changer le statut (ex: passer à "en cours" ou "terminee") et ajouter le coût final.
     */
    #[OA\Put(
        path: '/api/v1/maintenances/{id}',
        summary: 'Mettre à jour une maintenance',
        description: 'Permet de modifier le statut, le coût final ou la date de fin effective.',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['planifiee', 'en_cours', 'terminee', 'annulee'], example: 'terminee'),
                    new OA\Property(property: 'cost', type: 'number', example: 75000),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time', example: '2026-02-25 17:00:00')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Maintenance mise à jour'),
            new OA\Response(response: 404, description: 'Maintenance introuvable')
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

    /**
     * Annulation/Suppression
     * * Supprime l'entrée de maintenance de la base de données.
     */
    #[OA\Delete(
        path: '/api/v1/maintenances/{id}',
        summary: 'Supprimer une maintenance',
        security: [['sanctum' => []]],
        tags: ['Maintenances'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Maintenance supprimée'),
            new OA\Response(response: 403, description: 'Admin uniquement'),
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