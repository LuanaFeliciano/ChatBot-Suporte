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
            $table->boolean('was_fresh_session')->nullable()->after('was_helpful');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_messages', function (Blueprint $table) {
            $table->dropColumn('was_fresh_session');
        });
    }
};
