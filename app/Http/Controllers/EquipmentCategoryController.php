<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Catégories Équipements', description: 'Organisation et classification du parc matériel')]
class EquipmentCategoryController extends Controller
{
    /**
     * Liste des catégories
     * * Récupère l'ensemble des catégories avec un compteur automatique du nombre d'équipements rattachés.
     */
    #[OA\Get(
        path: '/api/v1/equipment-categories',
        summary: 'Lister toutes les catégories',
        description: 'Retourne la liste des catégories. Le champ `equipments_count` permet de connaître le volume d\'appareils par catégorie.',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Liste récupérée avec succès',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Maintenance Industrielle'),
                            new OA\Property(property: 'equipments_count', type: 'integer', example: 12, description: 'Nombre d\'équipements liés')
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Session non authentifiée')
        ]
    )]
    public function index()
    {
        $categories = EquipmentCategory::withCount('equipments')->get();
        return response()->json($categories, 200);
    }

    /**
     * Création de catégorie
     * * Action réservée aux administrateurs. Le nom doit être unique.
     */
    #[OA\Post(
        path: '/api/v1/equipment-categories',
        summary: 'Créer une nouvelle catégorie',
        description: 'Enregistre une catégorie. Utile pour filtrer les équipements par type (ex: Robotique, Informatique).',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Robotique', description: 'Nom unique de la catégorie')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Catégorie créée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — Droits admin requis'),
            new OA\Response(response: 422, description: 'Données invalides (ex: nom déjà pris)')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:equipment_categories,name',
        ]);
        $category = EquipmentCategory::create([
            'name' => $request->name,
        ]);
        return response()->json([
            'message'  => 'Catégorie créée avec succès',
            'category' => $category
        ], 201);
    }

    /**
     * Consultation d'une catégorie spécifique
     * * Affiche les détails d'une catégorie ainsi que la liste exhaustive des équipements qui lui appartiennent.
     */
    #[OA\Get(
        path: '/api/v1/equipment-categories/{id}',
        summary: 'Voir le détail d\'une catégorie',
        description: 'Retourne les informations de la catégorie et la liste des équipements associés.',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        parameters: [
            new OA\Parameter(
                name: 'id', 
                in: 'path', 
                required: true, 
                description: 'ID unique de la catégorie',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Détails retournés avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'equipments', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string', example: 'Bras Articulé X-200')
                            ]
                        ))
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Catégorie introuvable')
        ]
    )]
    public function show($id)
    {
        $category = EquipmentCategory::with('equipments')->find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Catégorie non trouvée'
            ], 404);
        }
        return response()->json($category, 200);
    }

    /**
     * Mise à jour d'une catégorie
     */
    #[OA\Put(
        path: '/api/v1/equipment-categories/{id}',
        summary: 'Modifier une catégorie',
        description: 'Permet de renommer une catégorie. La contrainte d\'unicité du nom est conservée.',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Robotique Industrielle')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Catégorie renommée avec succès'),
            new OA\Response(response: 403, description: 'Accès interdit'),
            new OA\Response(response: 404, description: 'Catégorie introuvable')
        ]
    )]
    public function update(Request $request, $id)
    {
        $category = EquipmentCategory::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Catégorie non trouvée'
            ], 404);
        }
        $request->validate([
            'name' => 'required|string|max:255|unique:equipment_categories,name,' . $id,
        ]);
        $category->update(['name' => $request->name]);
        return response()->json([
            'message'  => 'Catégorie modifiée avec succès',
            'category' => $category
        ], 200);
    }

    /**
     * Suppression de catégorie
     * * Sécurité : La suppression est bloquée si des équipements sont rattachés à cette catégorie pour éviter les données orphelines.
     */
    #[OA\Delete(
        path: '/api/v1/equipment-categories/{id}',
        summary: 'Supprimer une catégorie',
        description: 'Action irréversible. Impossible si la catégorie contient encore des équipements.',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Catégorie supprimée'),
            new OA\Response(response: 403, description: 'Droits admin requis'),
            new OA\Response(response: 409, description: 'Conflit : Catégorie non vide (équipements liés)'),
            new OA\Response(response: 404, description: 'Catégorie introuvable')
        ]
    )]
    public function destroy($id)
    {
        $category = EquipmentCategory::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Catégorie non trouvée'
            ], 404);
        }

        if ($category->equipments()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer — des équipements sont liés à cette catégorie'
            ], 409);
        }
        
        $category->delete();
        return response()->json([
            'message' => 'Catégorie supprimée avec succès'
        ], 200);
    }
}