<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_category_id')->constrained('equipment_categories');
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('serial_number')->unique();
            $table->date('installation_date')->nullable();
            $table->enum('status', ['actif', 'en_panne', 'en_maintenance', 'hors_service'])->default('actif');
            $table->string('location')->nullable();
            $table->string('picture')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};