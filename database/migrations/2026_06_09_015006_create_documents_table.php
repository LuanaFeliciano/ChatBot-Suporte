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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('original_filename');
            $table->string('openai_file_id')->nullable()->unique();
            $table->string('vector_store_file_id')->nullable()->unique();
            $table->string('status', 50)->default('pending');
            $table->json('attributes')->nullable();
            $table->text('error_message')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('openai_file_id');
            $table->index('vector_store_file_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
