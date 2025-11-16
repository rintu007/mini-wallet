<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add index for balance queries
            $table->index(['id', 'balance']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Add composite indexes for faster queries
            $table->index(['sender_id', 'created_at']);
            $table->index(['receiver_id', 'created_at']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['id', 'balance']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['sender_id', 'created_at']);
            $table->dropIndex(['receiver_id', 'created_at']);
            $table->dropIndex(['created_at']);
        });
    }
};