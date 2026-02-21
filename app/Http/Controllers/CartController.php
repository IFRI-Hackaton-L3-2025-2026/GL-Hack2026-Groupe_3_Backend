<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Panier', description: 'Gestion du panier d\'achat pour les utilisateurs connectés')]
class CartController extends Controller
{
    private function getOrCreateCart($userId)
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Afficher le contenu du panier
     * * Récupère tous les articles ajoutés avec le détail des produits et calcule le montant total global.
     */
    #[OA\Get(
        path: '/api/v1/cart',
        summary: 'Voir mon panier',
        description: 'Retourne la liste des articles dans le panier de l\'utilisateur authentifié, incluant les prix unitaires et le total général.',
        security: [['sanctum' => []]],
        tags: ['Panier'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Contenu du panier récupéré',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'cart_id', type: 'integer', example: 10),
                            new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                    new OA\Property(property: 'product', type: 'object', properties: [
                                        new OA\Property(property: 'name', type: 'string', example: 'Pneu Michelin'),
                                        new OA\Property(property: 'price', type: 'number', example: 45000)
                                    ])
                                ]
                            )),
                            new OA\Property(property: 'total', type: 'string', example: '90,000.00')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Utilisateur non authentifié')
        ]
    )]
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request->user()->id);
        $cart->load('items.product');

        $total = $cart->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'cart_id' => $cart->id,
                'items'   => $cart->items,
                'total'   => number_format($total, 2),
            ],
        ], 200);
    }

    /**
     * Ajouter un produit au panier
     * * Vérifie automatiquement la disponibilité en stock avant l'ajout. 
     * Si le produit existe déjà dans le panier, la quantité est mise à jour par addition.
     */
    #[OA\Post(
        path: '/api/v1/cart/items',
        summary: 'Ajouter un article',
        description: 'Ajoute un produit au panier. Bloque l\'ajout si le produit est inactif ou si le stock est insuffisant.',
        security: [['sanctum' => []]],
        tags: ['Panier'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer', example: 1, description: 'ID du produit à ajouter'),
                    new OA\Property(property: 'quantity', type: 'integer', example: 2, description: 'Nombre d\'unités souhaitées')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Produit ajouté avec succès'),
            new OA\Response(response: 400, description: 'Stock insuffisant ou produit indisponible'),
            new OA\Response(response: 422, description: 'Erreur de validation des paramètres')
        ]
    )]
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $product = Product::find($request->product_id);

        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'est plus disponible',
            ], 400);
        }

        $cart = $this->getOrCreateCart($request->user()->id);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = $cartItem ? $cartItem->quantity + $request->quantity : $request->quantity;

        if ($newQuantity > $product->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant. Stock disponible : ' . $product->stock_quantity,
            ], 400);
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cartItem = CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $product->id,
                'quantity'   => $request->quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'data'    => $cartItem->load('product'),
        ], 200);
    }

    /**
     * Modifier la quantité d'un article
     * * Permet d'ajuster précisément la quantité d'un article déjà présent dans le panier.
     */
    #[OA\Put(
        path: '/api/v1/cart/items/{cartItemId}',
        summary: 'Modifier la quantité',
        description: 'Met à jour la quantité d\'un article spécifique via son ID de ligne de panier.',
        security: [['sanctum' => []]],
        tags: ['Panier'],
        parameters: [
            new OA\Parameter(name: 'cartItemId', in: 'path', required: true, description: 'ID de la ligne du panier', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Quantité mise à jour'),
            new OA\Response(response: 404, description: 'L\'article n\'est pas dans votre panier'),
            new OA\Response(response: 400, description: 'Le stock est insuffisant pour cette nouvelle quantité')
        ]
    )]
    public function updateItem(Request $request, $cartItemId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $cart     = $this->getOrCreateCart($request->user()->id);
        $cartItem = CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans votre panier',
            ], 404);
        }

        if ($request->quantity > $cartItem->product->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant. Stock disponible : ' . $cartItem->product->stock_quantity,
            ], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data'    => $cartItem->load('product'),
        ], 200);
    }

    /**
     * Retirer un article du panier
     */
    #[OA\Delete(
        path: '/api/v1/cart/items/{cartItemId}',
        summary: 'Supprimer un article',
        description: 'Retire définitivement une ligne de produit du panier.',
        security: [['sanctum' => []]],
        tags: ['Panier'],
        parameters: [
            new OA\Parameter(name: 'cartItemId', in: 'path', required: true, description: 'ID de la ligne du panier', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Article retiré avec succès'),
            new OA\Response(response: 404, description: 'Article introuvable')
        ]
    )]
    public function removeItem(Request $request, $cartItemId)
    {
        $cart     = $this->getOrCreateCart($request->user()->id);
        $cartItem = CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans votre panier',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier',
        ], 200);
    }

    /**
     * Vider le panier
     * * Supprime tous les articles présents dans le panier de l'utilisateur.
     */
    #[OA\Delete(
        path: '/api/v1/cart',
        summary: 'Vider tout le panier',
        description: 'Supprime l\'intégralité des articles du panier en une seule action.',
        security: [['sanctum' => []]],
        tags: ['Panier'],
        responses: [
            new OA\Response(response: 200, description: 'Panier réinitialisé avec succès')
        ]
    )]
    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request->user()->id);
        $cart->items()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès',
        ], 200);
    }
}