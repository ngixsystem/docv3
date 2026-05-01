<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registry_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'department_id']);
        });

        // Auto-grant managers/employees access to their own department's registry
        DB::statement("
            INSERT INTO registry_access (user_id, department_id, created_at, updated_at)
            SELECT id, department_id, NOW(), NOW()
            FROM users
            WHERE department_id IS NOT NULL
              AND role NOT IN ('admin', 'clerk')
              AND deleted_at IS NULL
            ON CONFLICT (user_id, department_id) DO NOTHING
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_access');
    }
};
