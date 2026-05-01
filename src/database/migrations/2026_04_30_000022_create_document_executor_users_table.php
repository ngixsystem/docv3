<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_executor_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_comment')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'user_id']);
        });

        DB::statement(<<<'SQL'
            INSERT INTO document_executor_users (document_id, user_id, completed_at, created_at, updated_at)
            SELECT id, executor_id, CASE WHEN status = 'approved' THEN updated_at ELSE NULL END, NOW(), NOW()
            FROM documents
            WHERE executor_id IS NOT NULL
            ON CONFLICT (document_id, user_id) DO NOTHING
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_executor_users');
    }
};
