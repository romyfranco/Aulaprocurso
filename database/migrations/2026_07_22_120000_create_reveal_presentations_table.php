<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reveal_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->uuid('version')->unique();
            $table->string('status', 24)->default('processing')->index();
            $table->string('original_name');
            $table->string('archive_path');
            $table->string('storage_path')->nullable();
            $table->string('entry_path')->default('index.html');
            $table->unsignedBigInteger('archive_size')->default(0);
            $table->unsignedBigInteger('extracted_size')->default(0);
            $table->unsignedInteger('file_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->string('pending_reveal_archive')->nullable()->after('content');
            $table->string('pending_reveal_original_name')->nullable()->after('pending_reveal_archive');
            $table->foreignId('active_reveal_presentation_id')
                ->nullable()
                ->after('pending_reveal_original_name')
                ->constrained('reveal_presentations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_reveal_presentation_id');
            $table->dropColumn(['pending_reveal_archive', 'pending_reveal_original_name']);
        });

        Schema::dropIfExists('reveal_presentations');
    }
};
