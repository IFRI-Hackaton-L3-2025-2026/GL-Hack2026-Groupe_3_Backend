<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

#[OA\Tag(name: 'Product Categories', description: 'Gestion des catégories de produits (Admin uniquement)')]

class ProductCategoryController extends Controller
{
    #[OA\Get(
        path: '/api/v1/product-categories',
        summary: 'Lister toutes les catégories',
        security: [['sanctum' => []]],
        tags: ['Product Categories'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des catégories retournée avec succès')
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

    #[OA\Get(
        path: '/api/v1/product-categories/{id}',
        summary: 'Afficher une catégorie',
        security: [['sanctum' => []]],
        tags: ['Product Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
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

     #[OA\Post(
        path: '/api/v1/admin/product-categories',
        summary: 'Créer une catégorie (Admin)',
        security: [['sanctum' => []]],
        tags: ['Product Categories'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Pièces moteur'),
                    new OA\Property(property: 'description', type: 'string', example: 'Toutes les pièces moteur')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Catégorie créée avec succès'),
            new OA\Response(response: 422, description: 'Erreur de validation')
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

    #[OA\Put(
        path: '/api/v1/admin/product-categories/{id}',
        summary: 'Modifier une catégorie (Admin)',
        security: [['sanctum' => []]],
        tags: ['Product Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Catégorie modifiée avec succès'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée')
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

    #[OA\Delete(
        path: '/api/v1/admin/product-categories/{id}',
        summary: 'Supprimer une catégorie (Admin)',
        security: [['sanctum' => []]],
        tags: ['Product Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
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
