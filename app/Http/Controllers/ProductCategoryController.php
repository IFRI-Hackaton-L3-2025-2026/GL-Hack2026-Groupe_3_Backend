<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
/**
 * @OA\Tag(
 *     name="Product Categories",
 *     description="API pour gérer les catégories de produits"
 * )
 */

class ProductCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/product-categories",
     *     summary="Lister toutes les catégories",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Liste des catégories retournée avec succès")
     * )
     */
    public function index()
    {
        $categories = ProductCategory::all();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/product-categories/{id}",
     *     summary="Afficher une catégorie",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Catégorie trouvée"),
     *     @OA\Response(response=404, description="Catégorie non trouvée")
     * )
     */
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
     * @OA\Post(
     *     path="/api/product-categories",
     *     summary="Créer une catégorie",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Pièces moteur"),
     *             @OA\Property(property="description", type="string", example="Toutes les pièces moteur")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Catégorie créée avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
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
     * @OA\Put(
     *     path="/api/product-categories/{id}",
     *     summary="Modifier une catégorie",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Carrosserie"),
     *             @OA\Property(property="description", type="string", example="Pièces de carrosserie")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Catégorie modifiée avec succès"),
     *     @OA\Response(response=404, description="Catégorie non trouvée"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
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
     * @OA\Delete(
     *     path="/api/product-categories/{id}",
     *     summary="Supprimer une catégorie",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Catégorie supprimée avec succès"),
     *     @OA\Response(response=404, description="Catégorie non trouvée")
     * )
     */
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
