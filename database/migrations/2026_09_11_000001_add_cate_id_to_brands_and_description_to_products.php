<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->foreignId('cate_id')->nullable()->after('brand_name')->constrained('categories', 'cate_id')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cate_id');
        });
    }
};
