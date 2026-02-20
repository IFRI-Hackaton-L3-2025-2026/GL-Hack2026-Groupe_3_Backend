<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use Illuminate\Http\Request;

class EquipmentCategoryController extends Controller
{
    // Liste toutes les catégories
    public function index()
    {
        $categories = EquipmentCategory::withCount('equipments')->get();

        return response()->json($categories, 200);
    }

    // Créer une catégorie
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
