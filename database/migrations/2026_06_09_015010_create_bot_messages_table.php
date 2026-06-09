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
        Schema::create('bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels');
            $table->string('channel_user');
            $table->string('user_name')->nullable();
            $table->text('question');
            $table->text('answer');
            $table->smallInteger('response_ms')->nullable();
            $table->boolean('was_helpful')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'channel_user']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_messages');
    }
};
