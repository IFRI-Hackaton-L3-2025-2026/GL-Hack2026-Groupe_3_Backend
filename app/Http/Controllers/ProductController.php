<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Produits', description: 'Catalogue des pièces et gestion des stocks')]
class ProductController extends Controller
{
    /**
     * Catalogue public
     * * Liste les produits actifs uniquement. Inclut la pagination et les informations de catégorie.
     */
    #[OA\Get(
        path: '/api/v1/products',
        summary: 'Lister les produits',
        description: 'Retourne une liste paginée des produits marqués comme actifs.',
        security: [['sanctum' => []]],
        tags: ['Produits'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Numéro de la page', required: false, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Liste paginée retournée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'total', type: 'integer', example: 50)
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->paginate(10);

        return response()->json([
            'success'      => true,
            'data'         => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'per_page'     => $products->perPage(),
            'total'        => $products->total(),
        ], 200);
    }

    /**
     * Fiche produit
     */
    #[OA\Get(
        path: '/api/v1/products/{id}',
        summary: 'Détail d\'un produit',
        description: 'Récupère les informations complètes d\'un produit, y compris sa catégorie.',
        security: [['sanctum' => []]],
        tags: ['Produits'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Produit trouvé'),
            new OA\Response(response: 404, description: 'Produit non trouvé')
        ]
    )]
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $product,
        ], 200);
    }

    /**
     * Création de produit (Admin)
     * * Requiert l'envoi d'un formulaire multipart pour l'image.
     */
    #[OA\Post(
        path: '/api/v1/admin/products',
        summary: 'Créer un produit (Admin)',
        description: 'Ajoute un produit au catalogue avec possibilité d\'uploader une image.',
        security: [['sanctum' => []]],
        tags: ['Produits'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['product_category_id', 'name', 'price', 'stock_quantity'],
                    properties: [
                        new OA\Property(property: 'product_category_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Alternateur 12V'),
                        new OA\Property(property: 'description', type: 'string', example: 'Alternateur haute performance pour moteurs industriels'),
                        new OA\Property(property: 'price', type: 'number', example: 12500.50),
                        new OA\Property(property: 'stock_quantity', type: 'integer', example: 15),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Fichier image (jpeg, png, webp)'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Produit créé avec succès'),
            new OA\Response(response: 422, description: 'Erreur de validation des données ou du fichier')
        ]
    )]
    public function store(Request $request)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        $validator = Validator::make($request->all(), [
            'product_category_id' => 'required|exists:product_categories,id',
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'stock_quantity'      => 'required|integer|min:0',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active'           => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data'    => $product,
        ], 201);
    }

    /**
     * Modification de produit (Admin)
     * * Note : Pour l'upload d'image en modification, utilisez POST avec le champ _method=PUT.
     */
    #[OA\Post(
        path: '/api/v1/admin/products/{id}',
        summary: 'Modifier un produit (Admin)',
        description: 'Met à jour un produit existant. Si vous changez l\'image, l\'ancienne sera automatiquement supprimée du stockage.',
        security: [['sanctum' => []]],
        tags: ['Produits'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Obligatoire pour simuler un PUT avec des fichiers'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'price', type: 'number'),
                        new OA\Property(property: 'stock_quantity', type: 'integer'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Produit modifié avec succès'),
            new OA\Response(response: 404, description: 'Produit non trouvé')
        ]
    )]
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        $validator = Validator::make($request->all(), [
            'product_category_id' => 'sometimes|exists:product_categories,id',
            'name'                => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'price'               => 'sometimes|numeric|min:0',
            'stock_quantity'      => 'sometimes|integer|min:0',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active'           => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Produit modifié avec succès',
            'data'    => $product,
        ], 200);
    }

    /**
     * Suppression (Admin)
     * * Supprime le produit et son fichier image associé du disque public.
     */
    #[OA\Delete(
        path: '/api/v1/admin/products/{id}',
        summary: 'Supprimer un produit (Admin)',
        security: [['sanctum' => []]],
        tags: ['Produits'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Produit et image supprimés avec succès'),
            new OA\Response(response: 404, description: 'Produit non trouvé')
        ]
    )]
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès',
        ], 200);
    }
}