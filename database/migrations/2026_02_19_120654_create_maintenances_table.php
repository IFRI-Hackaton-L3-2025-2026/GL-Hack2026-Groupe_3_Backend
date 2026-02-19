<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', ['preventive', 'corrective']);
            $table->enum('status', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};