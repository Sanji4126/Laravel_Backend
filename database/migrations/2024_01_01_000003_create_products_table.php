<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name');
            $table->foreignId('cate_id')->constrained('categories', 'cate_id')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands', 'brand_id')->cascadeOnDelete();
            $table->integer('stock')->default(0);
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
