<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_recipient_users', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_id', 'user_id']);
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->json('recipient_orgs')->nullable()->after('recipient_org');
        });

        // Migrate existing single recipient_id → pivot table
        DB::statement("
            INSERT INTO document_recipient_users (document_id, user_id, created_at, updated_at)
            SELECT id, recipient_id, NOW(), NOW()
            FROM documents
            WHERE recipient_id IS NOT NULL
        ");

        // Migrate existing single recipient_org → recipient_orgs JSON array
        DB::statement("
            UPDATE documents
            SET recipient_orgs = json_build_array(recipient_org)
            WHERE recipient_org IS NOT NULL AND recipient_org != ''
        ");

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['recipient_id']);
            $table->dropColumn(['recipient_id', 'recipient_org']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_org')->nullable();
        });

        Schema::dropIfExists('document_recipient_users');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('recipient_orgs');
        });
    }
};
