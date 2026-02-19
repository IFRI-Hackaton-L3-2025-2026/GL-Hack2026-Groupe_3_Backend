<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments');
            $table->foreignId('user_id')->constrained('users');
            $table->text('description');
            $table->enum('priority', ['faible', 'moyenne', 'critique']);
            $table->enum('status', ['ouverte', 'en_cours', 'resolue'])->default('ouverte');
            $table->dateTime('reported_at');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breakdowns');
    }
};