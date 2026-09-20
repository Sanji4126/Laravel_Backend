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
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();

            // which user own this token
            $table->foreignId('user_id')
                    ->constrained('users', 'user_id')
                    ->cascadeOnDelete();

            // The refresh token
            $table->string('token',255)->unique();

            // When token Expires
            $table->timestamp('expires_at');

            //when Token was Revoked
            $table->boolean('revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
