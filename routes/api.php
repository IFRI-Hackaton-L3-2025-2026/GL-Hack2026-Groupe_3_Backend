<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\BreakdownController;
use App\Http\Controllers\DashboardController;

// Routes publiques
Route::prefix('v1')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']); // clients Flutter
});

//  Routes protégées
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Profil & déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Création personnel — admin seulement
    Route::post('/admin/users', [AuthController::class, 'createStaff'])
         ->middleware('role:admin');

    //  Dashboard 
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
    Route::post('/breakdowns',     [BreakdownController::class, 'store'])->middleware('role:admin,gestionnaire,technicien');
    Route::get('/breakdowns/{id}', [BreakdownController::class, 'show']);
    Route::put('/breakdowns/{id}', [BreakdownController::class, 'update'])->middleware('role:admin,gestionnaire,technicien');
    Route::delete('/breakdowns/{id}', [BreakdownController::class, 'destroy'])->middleware('role:admin');

});

