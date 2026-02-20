<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;


/**
 * @OA\Tag(
 *     name="Cart",
 *     description="Gestion du panier (Utilisateur connecté)"
 * )
 */
class CartController extends Controller
{
    /**
     * Récupère ou crée le panier actif de l'utilisateur connecté
     */
    private function getOrCreateCart($userId)
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * @OA\Get(
     *     path="/api/cart",
     *     summary="Afficher le panier de l'utilisateur connecté",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Panier retourné avec succès")
     * )
     */
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
     * @OA\Post(
     *     path="/api/cart/items",
     *     summary="Ajouter un produit au panier",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Produit ajouté au panier"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=400, description="Stock insuffisant")
     * )
     */
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

        // Si le produit est déjà dans le panier, on additionne les quantités
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

        $cartItem->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'data'    => $cartItem,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/cart/items/{cartItemId}",
     *     summary="Modifier la quantité d'un article du panier",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="cartItemId", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Quantité modifiée"),
     *     @OA\Response(response=404, description="Article non trouvé"),
     *     @OA\Response(response=400, description="Stock insuffisant")
     * )
     */
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
     * @OA\Delete(
     *     path="/api/cart/items/{cartItemId}",
     *     summary="Supprimer un article du panier",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="cartItemId", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Article supprimé du panier"),
     *     @OA\Response(response=404, description="Article non trouvé")
     * )
     */
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
     * @OA\Delete(
     *     path="/api/cart",
     *     summary="Vider tout le panier",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Panier vidé avec succès")
     * )
     */
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