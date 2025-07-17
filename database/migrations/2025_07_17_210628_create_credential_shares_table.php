<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credential_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with_group_id')->nullable()->constrained('groups')->onDelete('cascade');
            $table->enum('permission', ['read', 'write'])->default('read');
            $table->timestamps();

            // Unicidad: solo puede compartirse con un usuario o con un grupo, no ambos a la vez
            $table->unique(['credential_id', 'shared_with_user_id']);
            $table->unique(['credential_id', 'shared_with_group_id']);
            $table->index(['shared_with_user_id', 'credential_id']);
            $table->index(['shared_with_group_id', 'credential_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credential_shares');
    }
};
