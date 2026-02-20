<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


#[OA\Tag(name: 'Products', description: 'Gestion des produits')]

class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/v1/products',
        summary: 'Lister les produits avec pagination',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée des produits')
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

    #[OA\Get(
        path: '/api/v1/products/{id}',
        summary: 'Afficher un produit',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
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

    #[OA\Post(
        path: '/api/v1/admin/products',
        summary: 'Créer un produit (Admin)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['product_category_id', 'name', 'price', 'stock_quantity'],
                    properties: [
                        new OA\Property(property: 'product_category_id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'price', type: 'number'),
                        new OA\Property(property: 'stock_quantity', type: 'integer'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Produit créé avec succès'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]


    public function store(Request $request)
    {
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

    #[OA\Post(
        path: '/api/v1/admin/products/{id}',
        summary: 'Modifier un produit (Admin) - POST avec _method=PUT pour image',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT'),
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

    #[OA\Delete(
        path: '/api/v1/admin/products/{id}',
        summary: 'Supprimer un produit (Admin)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Produit supprimé avec succès'),
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
