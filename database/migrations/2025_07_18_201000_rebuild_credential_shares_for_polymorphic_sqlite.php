<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // 1. Crear tabla temporal con la nueva estructura
        Schema::create('credential_shares_temp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credential_id');
            $table->unsignedBigInteger('shared_by_user_id');
            $table->string('shared_with_type');
            $table->unsignedBigInteger('shared_with_id');
            $table->string('permission')->default('read');
            $table->timestamps();
        });

        // 2. Copiar datos existentes
        $db = \DB::connection()->getPdo();
        $db->exec('INSERT INTO credential_shares_temp (id, credential_id, shared_by_user_id, shared_with_type, shared_with_id, permission, created_at, updated_at)
            SELECT id, credential_id, shared_by_user_id,
                CASE WHEN shared_with_user_id IS NOT NULL THEN "App\\Models\\User" ELSE "App\\Models\\Group" END as shared_with_type,
                COALESCE(shared_with_user_id, shared_with_group_id) as shared_with_id,
                permission, created_at, updated_at
            FROM credential_shares');

        // 3. Eliminar tabla original
        Schema::drop('credential_shares');

        // 4. Renombrar tabla temporal
        Schema::rename('credential_shares_temp', 'credential_shares');
    }

    public function down()
    {
        // No reversible, solo para desarrollo
    }
};
