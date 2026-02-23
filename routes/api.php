<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\BreakdownController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;

// Routes publiques
Route::prefix('v1')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']); // clients Flutter
});

//  Routes protégées
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

   // Profil & Sécurité
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    
    // NOUVELLE ROUTE : Mise à jour mot de passe
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    /* --- Espace Admin --- */
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users', [AuthController::class, 'createStaff']);
        
        // NOUVELLE ROUTE : Voir tout le personnel (Techniciens/Gestionnaires)
        Route::get('/admin/staff', [AuthController::class, 'getStaff']);
    });

    // --- Dashboard & Maintenance (Gardez vos routes existantes ici) ---
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Catégories équipements
    Route::get('/equipment-categories',       [EquipmentCategoryController::class, 'index']);
    Route::post('/equipment-categories',      [EquipmentCategoryController::class, 'store'])->middleware('role:admin');
    Route::get('/equipment-categories/{id}',  [EquipmentCategoryController::class, 'show']);
    Route::put('/equipment-categories/{id}',  [EquipmentCategoryController::class, 'update'])->middleware('role:admin');
    Route::delete('/equipment-categories/{id}', [EquipmentCategoryController::class, 'destroy'])->middleware('role:admin');

    // Equipements 
    Route::get('/equipments',      [EquipmentController::class, 'index']);
    Route::post('/equipments',     [EquipmentController::class, 'store'])->middleware('role:admin,gestionnaire');
    Route::get('/equipments/{id}', [EquipmentController::class, 'show']);
    Route::put('/equipments/{id}', [EquipmentController::class, 'update'])->middleware('role:admin,gestionnaire');
    Route::delete('/equipments/{id}', [EquipmentController::class, 'destroy'])->middleware('role:admin');

    // Maintenances
    Route::get('/maintenances',      [MaintenanceController::class, 'index']);
    Route::post('/maintenances',     [MaintenanceController::class, 'store'])->middleware('role:admin,gestionnaire');
    Route::get('/maintenances/{id}', [MaintenanceController::class, 'show']);
    Route::put('/maintenances/{id}', [MaintenanceController::class, 'update'])->middleware('role:admin,gestionnaire,technicien');
    Route::delete('/maintenances/{id}', [MaintenanceController::class, 'destroy'])->middleware('role:admin');

    // Pannes 
    Route::get('/breakdowns',      [BreakdownController::class, 'index']);
    Route::post('/breakdowns',     [BreakdownController::class, 'store'])->middleware('role:admin,technicien');
    Route::get('/breakdowns/{id}', [BreakdownController::class, 'show']);
    Route::put('/breakdowns/{id}', [BreakdownController::class, 'update'])->middleware('role:admin,technicien');
    Route::delete('/breakdowns/{id}', [BreakdownController::class, 'destroy'])->middleware('role:admin');

    

});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Product Categories — lecture pour tous les connectés
    Route::get('product-categories', [ProductCategoryController::class, 'index']);
    Route::get('product-categories/{id}', [ProductCategoryController::class, 'show']);

    // Products — lecture pour tous les connectés
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);

    // Cart — client uniquement
    Route::middleware('role:client')->group(function () {
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::put('cart/items/{cartItemId}', [CartController::class, 'updateItem']);
        Route::delete('cart/items/{cartItemId}', [CartController::class, 'removeItem']);
        Route::delete('cart', [CartController::class, 'clear']);
    });

    // Orders — client uniquement
    Route::middleware('role:client')->group(function () {
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::post('orders/checkout', [OrderController::class, 'checkout']);
        Route::put('orders/{id}/cancel', [OrderController::class, 'cancel']);
    });

    // Payments — client uniquement
    Route::middleware('role:client')->group(function () {
        Route::get('payments', [PaymentController::class, 'userPayments']);
        Route::get('payments/{id}', [PaymentController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Product Categories — gestion admin
        Route::post('product-categories', [ProductCategoryController::class, 'store']);
        Route::put('product-categories/{id}', [ProductCategoryController::class, 'update']);
        Route::delete('product-categories/{id}', [ProductCategoryController::class, 'destroy']);

        // Products — gestion admin
        Route::post('products', [ProductController::class, 'store']);
        Route::post('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);

        // Orders — gestion statuts admin
        Route::get('orders', [OrderController::class, 'adminIndex']);
        Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']);

        // Payments — vue admin
        Route::get('payments', [PaymentController::class, 'index']);

        // Clients — gestion admin
        Route::get('clients', [UserController::class, 'index']);
        Route::get('clients/{id}', [UserController::class, 'show']);
        Route::put('clients/{id}/block', [UserController::class, 'block']);
        Route::put('clients/{id}/unblock', [UserController::class, 'unblock']);

        // Dashboard — vue admin
        Route::get('dashboard/ecommerce', [DashboardController::class, 'ecommerce']);
    });
});

