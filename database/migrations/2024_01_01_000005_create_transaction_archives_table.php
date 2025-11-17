<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->decimal('commission_fee', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('status')->default('completed');
            $table->text('description')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamps();

            // Indexes for archive queries
            $table->index(['sender_id', 'archived_at']);
            $table->index(['receiver_id', 'archived_at']);
            $table->index(['archived_at']);
        });

        // Add archived_at column to transactions table for tracking
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('updated_at');
            $table->index(['created_at']); // Better indexing for archiving
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_archives');
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('archived_at');
            $table->dropIndex(['created_at']);
        });
    }
};