<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->index(['shared_with', 'status']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->index('title');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->index('original_name');
        });
    }

    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropIndex(['shared_with', 'status']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['title']);
        });

        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['original_name']);
        });
    }
};
