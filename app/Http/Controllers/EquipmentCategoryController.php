<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Catégories Équipements', description: 'Gestion des catégories d\'équipements')]
class EquipmentCategoryController extends Controller
{
    // Liste toutes les catégories
    #[OA\Get(
        path: '/api/v1/equipment-categories',
        summary: 'Liste toutes les catégories d\'équipements',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        responses: [
            new OA\Response(response: 200, description: 'Liste retournée avec succès'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function index()
    {
        $categories = EquipmentCategory::withCount('equipments')->get();
        return response()->json($categories, 200);
    }

    // Créer une catégorie
    #[OA\Post(
        path: '/api/v1/equipment-categories',
        summary: 'Créer une catégorie d\'équipement',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Robot')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Catégorie créée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — admin uniquement'),
            new OA\Response(response: 422, description: 'Données invalides')
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

    // Détail d'une catégorie
    #[OA\Get(
        path: '/api/v1/equipment-categories/{id}',
        summary: 'Détail d\'une catégorie avec ses équipements',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Catégorie retournée avec succès'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée')
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

    // Modifier une catégorie
    #[OA\Put(
        path: '/api/v1/equipment-categories/{id}',
        summary: 'Modifier une catégorie d\'équipement',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Robot Industriel')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Catégorie modifiée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — admin uniquement'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée')
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

    // Supprimer une catégorie
    #[OA\Delete(
        path: '/api/v1/equipment-categories/{id}',
        summary: 'Supprimer une catégorie d\'équipement',
        security: [['sanctum' => []]],
        tags: ['Catégories Équipements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Catégorie supprimée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — admin uniquement'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée'),
            new OA\Response(response: 409, description: 'Impossible de supprimer — équipements liés')
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
        // Vérifier qu'il n'y a pas d'équipements liés avant de supprimer
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