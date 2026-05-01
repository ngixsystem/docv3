<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registry_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users');
            $table->text('note')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->unique(['document_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_entries');
    }
};
