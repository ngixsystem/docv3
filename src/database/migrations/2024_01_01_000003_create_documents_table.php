<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->enum('type', ['incoming', 'outgoing', 'memo', 'internal'])->index();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_org')->nullable();
            $table->string('recipient_org')->nullable();
            $table->foreignId('executor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['draft', 'registered', 'review', 'approved', 'rejected', 'archive'])
                  ->default('draft');
            $table->date('doc_date');
            $table->date('deadline')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
