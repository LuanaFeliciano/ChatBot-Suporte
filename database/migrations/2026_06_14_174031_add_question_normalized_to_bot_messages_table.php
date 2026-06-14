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
        Schema::table('bot_messages', function (Blueprint $table) {
            $table->text('question_normalized')->nullable()->after('file_search_hit_count');
            $table->index('question_normalized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_messages', function (Blueprint $table) {
            $table->dropIndex(['question_normalized']);
            $table->dropColumn('question_normalized');
        });
    }
};
