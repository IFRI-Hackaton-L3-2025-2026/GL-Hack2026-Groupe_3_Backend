<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->dateTime('order_date');
        $table->decimal('subtotal_amount', 10, 2);
        $table->decimal('delivery_fee', 10, 2)->default(0);
        $table->decimal('total_amount', 10, 2);
        $table->string('delivery_type')->default('pickup');
        $table->string('delivery_address')->nullable();
        $table->enum('status', ['en_attente', 'confirmée', 'en_preparation', 'expédiée', 'prete_au_retrait', 'livrée', 'récupérée', 'annulée'])->default('en_attente');
        $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};