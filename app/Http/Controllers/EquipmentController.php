<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Équipements', description: 'Inventaire et cycle de vie des équipements industriels')]
class EquipmentController extends Controller
{
    /**
     * Liste des équipements
     * * Récupère la liste complète du matériel avec les détails de leurs catégories respectives.
     */
    #[OA\Get(
        path: '/api/v1/equipments',
        summary: 'Lister tous les équipements',
        description: 'Retourne l\'inventaire complet du parc matériel.',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Inventaire récupéré avec succès',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Compresseur Atlas Copco'),
                            new OA\Property(property: 'status', type: 'string', example: 'actif'),
                            new OA\Property(property: 'category', type: 'object', properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Air Comprimé')
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
        $equipments = Equipment::with('category')->get();
        return response()->json($equipments, 200);
    }

    /**
     * Enregistrer un nouvel équipement
     * * Ajoute une machine à l'inventaire. Le numéro de série doit être unique.
     */
    #[OA\Post(
        path: '/api/v1/equipments',
        summary: 'Créer un équipement',
        description: 'Ajoute un nouvel équipement au système avec son statut initial.',
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
                    new OA\Property(property: 'status', type: 'string', enum: ['actif', 'en_panne', 'en_maintenance', 'hors_service'], example: 'actif'),
                    new OA\Property(property: 'location', type: 'string', example: 'Zone A'),
                    new OA\Property(property: 'picture', type: 'string', example: 'equipments/robot.jpg'),
                    new OA\Property(property: 'description', type: 'string', example: 'Bras articulé pour soudure haute précision')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Équipement enregistré avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — Privilèges insuffisants'),
            new OA\Response(response: 422, description: 'Erreur de validation (ex: numéro de série déjà existant)')
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

    /**
     * Fiche détaillée de l'équipement
     * * Affiche tout l'historique : informations générales, liste des maintenances passées et historique des pannes.
     */
    #[OA\Get(
        path: '/api/v1/equipments/{id}',
        summary: 'Voir les détails complets',
        description: 'Récupère un équipement avec tout son historique de maintenance et de pannes.',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de l\'équipement', schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Fiche équipement récupérée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'maintenances', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'breakdowns', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Équipement introuvable')
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

    /**
     * Mise à jour de l'équipement
     * * Permet de modifier la localisation, le statut ou les informations techniques.
     */
    #[OA\Put(
        path: '/api/v1/equipments/{id}',
        summary: 'Modifier un équipement',
        description: 'Met à jour les informations d\'une machine existante.',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['actif', 'en_panne', 'en_maintenance', 'hors_service']),
                    new OA\Property(property: 'location', type: 'string', example: 'Zone B'),
                    new OA\Property(property: 'description', type: 'string', example: 'Maintenance effectuée, RAS')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Équipement mis à jour avec succès'),
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

    /**
     * Supprimer un équipement
     * * Action irréversible. Généralement réservé au rôle Admin.
     */
    #[OA\Delete(
        path: '/api/v1/equipments/{id}',
        summary: 'Supprimer un équipement',
        description: 'Retire définitivement l\'équipement de la base de données.',
        security: [['sanctum' => []]],
        tags: ['Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Équipement supprimé avec succès'),
            new OA\Response(response: 403, description: 'Action interdite — Admin requis'),
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