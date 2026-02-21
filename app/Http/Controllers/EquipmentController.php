<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Équipements', description: 'Gestion des équipements industriels')]
class EquipmentController extends Controller
{
    // Liste tous les équipements
    #[OA\Get(
        path: '/api/v1/equipments',
        summary: 'Liste tous les équipements',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        responses: [
            new OA\Response(response: 200, description: 'Liste retournée avec succès'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function index()
    {
        $equipments = Equipment::with('category')->get();
        return response()->json($equipments, 200);
    }

    // Créer un équipement
    #[OA\Post(
        path: '/api/v1/equipments',
        summary: 'Créer un équipement',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['equipment_category_id', 'name', 'serial_number'],
                properties: [
                    new OA\Property(property: 'equipment_category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Robot KUKA KR 210'),
                    new OA\Property(property: 'brand', type: 'string', example: 'KUKA'),
                    new OA\Property(property: 'serial_number', type: 'string', example: 'KUKA-003'),
                    new OA\Property(property: 'installation_date', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'status', type: 'string', enum: ['actif', 'en_panne', 'en_maintenance', 'hors_service']),
                    new OA\Property(property: 'location', type: 'string', example: 'Zone A'),
                    new OA\Property(property: 'picture', type: 'string', example: 'equipments/robot.jpg'),
                    new OA\Property(property: 'description', type: 'string', example: 'Bras articulé pour soudure')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Équipement créé avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 422, description: 'Données invalides')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'equipment_category_id' => 'required|exists:equipment_categories,id',
            'name'                  => 'required|string|max:255',
            'brand'                 => 'nullable|string|max:255',
            'serial_number'         => 'required|string|unique:equipments,serial_number',
            'installation_date'     => 'nullable|date',
            'status'                => 'nullable|in:actif,en_panne,en_maintenance,hors_service',
            'location'              => 'nullable|string|max:255',
            'picture'               => 'nullable|string',
            'description'           => 'nullable|string',
        ]);
        $equipment = Equipment::create($request->all());
        return response()->json([
            'message'   => 'Équipement créé avec succès',
            'equipment' => $equipment->load('category')
        ], 201);
    }

    // Détail d'un équipement
    #[OA\Get(
        path: '/api/v1/equipments/{id}',
        summary: 'Détail d\'un équipement avec ses maintenances et pannes',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Équipement retourné avec succès'),
            new OA\Response(response: 404, description: 'Équipement non trouvé')
        ]
    )]
    public function show($id)
    {
        $equipment = Equipment::with(['category', 'maintenances', 'breakdowns'])->find($id);
        if (!$equipment) {
            return response()->json([
                'message' => 'Équipement non trouvé'
            ], 404);
        }
        return response()->json($equipment, 200);
    }

    // Modifier un équipement
    #[OA\Put(
        path: '/api/v1/equipments/{id}',
        summary: 'Modifier un équipement',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'equipment_category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Robot KUKA KR 210'),
                    new OA\Property(property: 'brand', type: 'string', example: 'KUKA'),
                    new OA\Property(property: 'serial_number', type: 'string', example: 'KUKA-003'),
                    new OA\Property(property: 'installation_date', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'status', type: 'string', enum: ['actif', 'en_panne', 'en_maintenance', 'hors_service']),
                    new OA\Property(property: 'location', type: 'string', example: 'Zone B'),
                    new OA\Property(property: 'picture', type: 'string', example: 'equipments/robot.jpg'),
                    new OA\Property(property: 'description', type: 'string', example: 'Bras articulé mis à jour')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Équipement modifié avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Équipement non trouvé')
        ]
    )]
    public function update(Request $request, $id)
    {
        $equipment = Equipment::find($id);
        if (!$equipment) {
            return response()->json([
                'message' => 'Équipement non trouvé'
            ], 404);
        }
        $request->validate([
            'equipment_category_id' => 'nullable|exists:equipment_categories,id',
            'name'                  => 'nullable|string|max:255',
            'brand'                 => 'nullable|string|max:255',
            'serial_number'         => 'nullable|string|unique:equipments,serial_number,' . $id,
            'installation_date'     => 'nullable|date',
            'status'                => 'nullable|in:actif,en_panne,en_maintenance,hors_service',
            'location'              => 'nullable|string|max:255',
            'picture'               => 'nullable|string',
            'description'           => 'nullable|string',
        ]);
        $equipment->update($request->all());
        return response()->json([
            'message'   => 'Équipement modifié avec succès',
            'equipment' => $equipment->load('category')
        ], 200);
    }

    // Supprimer un équipement
    #[OA\Delete(
        path: '/api/v1/equipments/{id}',
        summary: 'Supprimer un équipement',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Équipement supprimé avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — admin uniquement'),
            new OA\Response(response: 404, description: 'Équipement non trouvé')
        ]
    )]
    public function destroy($id)
    {
        $equipment = Equipment::find($id);
        if (!$equipment) {
            return response()->json([
                'message' => 'Équipement non trouvé'
            ], 404);
        }
        $equipment->delete();
        return response()->json([
            'message' => 'Équipement supprimé avec succès'
        ], 200);
    }
}