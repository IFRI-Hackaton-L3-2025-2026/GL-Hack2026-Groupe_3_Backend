<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Catégories Produits', description: 'Gestion du catalogue et des classifications de produits')]
class ProductCategoryController extends Controller
{
    /**
     * Liste des catégories
     * * Récupère l'ensemble des catégories disponibles pour le catalogue.
     */
    #[OA\Get(
        path: '/api/v1/product-categories',
        summary: 'Lister toutes les catégories',
        description: 'Retourne la liste simple de toutes les catégories enregistrées.',
        security: [['sanctum' => []]],
        tags: ['Catégories Produits'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Liste des catégories retournée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function index()
    {
        $categories = ProductCategory::all();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ], 200);
    }

    /**
     * Détail d'une catégorie
     */
    #[OA\Get(
        path: '/api/v1/product-categories/{id}',
        summary: 'Afficher une catégorie',
        description: 'Récupère les informations détaillées d\'une catégorie spécifique via son ID.',
        security: [['sanctum' => []]],
        tags: ['Catégories Produits'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la catégorie', schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Catégorie trouvée'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée')
        ]
    )]
    public function show($id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $category,
        ], 200);
    }

    /**
     * Création (Admin)
     * * Seuls les administrateurs peuvent ajouter de nouvelles catégories au catalogue.
     */
     #[OA\Post(
        path: '/api/v1/admin/product-categories',
        summary: 'Créer une catégorie (Admin)',
        description: 'Enregistre une nouvelle catégorie. Le nom doit être unique dans la base de données.',
        security: [['sanctum' => []]],
        tags: ['Catégories Produits'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Pièces moteur'),
                    new OA\Property(property: 'description', type: 'string', example: 'Toutes les pièces moteur et composants internes')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Catégorie créée avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé - Admin requis'),
            new OA\Response(response: 422, description: 'Erreur de validation (ex: nom déjà utilisé)')
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:product_categories,name',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $category = ProductCategory::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data'    => $category,
        ], 201);
    }

    /**
     * Mise à jour (Admin)
     */
    #[OA\Put(
        path: '/api/v1/admin/product-categories/{id}',
        summary: 'Modifier une catégorie (Admin)',
        description: 'Met à jour le nom ou la description d\'une catégorie existante.',
        security: [['sanctum' => []]],
        tags: ['Catégories Produits'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Électricité'),
                    new OA\Property(property: 'description', type: 'string', example: 'Composants électriques et faisceaux')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Catégorie modifiée avec succès'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée'),
            new OA\Response(response: 422, description: 'Données invalides')
        ]
    )]
    public function update(Request $request, $id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|string|max:255|unique:product_categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie modifiée avec succès',
            'data'    => $category,
        ], 200);
    }

    /**
     * Suppression (Admin)
     * * Supprime définitivement la catégorie de la base de données.
     */
    #[OA\Delete(
        path: '/api/v1/admin/product-categories/{id}',
        summary: 'Supprimer une catégorie (Admin)',
        description: 'Supprime une catégorie via son identifiant.',
        security: [['sanctum' => []]],
        tags: ['Catégories Produits'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Catégorie supprimée avec succès'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée')
        ]
    )]
    public function destroy($id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès',
        ], 200);
    }
}