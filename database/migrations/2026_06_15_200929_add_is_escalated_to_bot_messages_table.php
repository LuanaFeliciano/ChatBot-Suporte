<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bot_messages', function (Blueprint $table) {
            $table->boolean('is_escalated')->default(false)->after('was_helpful')->index();
        });

        DB::statement("update bot_messages set is_escalated = true where answer like '%Se o problema continuar, entre em contato pelo link informado.%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_messages', function (Blueprint $table) {
            $table->dropColumn('is_escalated');
        });
    }
};
