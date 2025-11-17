<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Application-level partitioning implementation
        $this->createYearlyPartitionTables();
        $this->addOptimizedIndexes();
        $this->addMissingIndexesToArchiveTable();
    }

    public function down(): void
    {
        $this->dropYearlyPartitionTables();
        $this->removeOptimizedIndexes();
        $this->removeAdditionalArchiveIndexes();
    }

    private function createYearlyPartitionTables()
    {
        $currentYear = date('Y');
        
        // Create partition tables for current year + next 3 years
        for ($year = $currentYear; $year <= $currentYear + 3; $year++) {
            $tableName = "transactions_{$year}";
            
            // Skip if table already exists
            if (Schema::hasTable($tableName)) {
                Log::info("Partition table already exists: {$tableName}");
                continue;
            }
            
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users');
                $table->foreignId('receiver_id')->constrained('users');
                $table->decimal('amount', 15, 2);
                $table->decimal('commission_fee', 15, 2);
                $table->decimal('total_amount', 15, 2);
                $table->string('status')->default('completed');
                $table->text('description')->nullable();
                $table->timestamps();

                // Optimized indexes for each partition table
                $table->index(['sender_id', 'created_at']);
                $table->index(['receiver_id', 'created_at']);
                $table->index(['created_at']);
                $table->index(['sender_id', 'receiver_id', 'created_at']);
            });
            
            Log::info("Created yearly partition table: {$tableName}");
        }
        
        Log::info('Yearly partition tables created successfully');
    }

    private function addOptimizedIndexes()
    {
        // Add indexes to main transactions table if they don't exist
        Schema::table('transactions', function (Blueprint $table) {
            // Check if index exists before adding
            if (!$this->indexExists('transactions', 'transactions_sender_id_receiver_id_created_at_index')) {
                $table->index(['sender_id', 'receiver_id', 'created_at']);
            }
            
            if (!$this->indexExists('transactions', 'transactions_created_at_status_index')) {
                $table->index(['created_at', 'status']);
            }
            
            if (!$this->indexExists('transactions', 'transactions_sender_id_created_at_status_index')) {
                $table->index(['sender_id', 'created_at', 'status']);
            }
            
            if (!$this->indexExists('transactions', 'transactions_receiver_id_created_at_status_index')) {
                $table->index(['receiver_id', 'created_at', 'status']);
            }
        });
        
        Log::info('Optimized indexes added to transactions table');
    }

    private function addMissingIndexesToArchiveTable()
    {
        // Add additional indexes to existing archive table
        Schema::table('transaction_archives', function (Blueprint $table) {
            // Check if index exists before adding
            if (!$this->indexExists('transaction_archives', 'transaction_archives_created_at_index')) {
                $table->index(['created_at']);
            }
            
            if (!$this->indexExists('transaction_archives', 'transaction_archives_sender_id_receiver_id_archived_at_index')) {
                $table->index(['sender_id', 'receiver_id', 'archived_at']);
            }
        });
        
        Log::info('Additional indexes added to transaction_archives table');
    }

    private function dropYearlyPartitionTables()
    {
        $currentYear = date('Y');
        
        // Drop partition tables for current year + next 3 years
        for ($year = $currentYear; $year <= $currentYear + 3; $year++) {
            $tableName = "transactions_{$year}";
            
            if (Schema::hasTable($tableName)) {
                Schema::dropIfExists($tableName);
                Log::info("Dropped yearly partition table: {$tableName}");
            }
        }
    }

    private function removeOptimizedIndexes()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $indexes = [
                'transactions_sender_id_receiver_id_created_at_index',
                'transactions_created_at_status_index', 
                'transactions_sender_id_created_at_status_index',
                'transactions_receiver_id_created_at_status_index'
            ];
            
            foreach ($indexes as $index) {
                if ($this->indexExists('transactions', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
        
        Log::info('Optimized indexes removed from transactions table');
    }

    private function removeAdditionalArchiveIndexes()
    {
        Schema::table('transaction_archives', function (Blueprint $table) {
            $indexes = [
                'transaction_archives_created_at_index',
                'transaction_archives_sender_id_receiver_id_archived_at_index'
            ];
            
            foreach ($indexes as $index) {
                if ($this->indexExists('transaction_archives', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
        
        Log::info('Additional indexes removed from transaction_archives table');
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $indexName): bool
    {
        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();
        
        try {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND index_name = ?
            ", [$databaseName, $table, $indexName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            Log::warning("Error checking index existence: {$e->getMessage()}");
            return false;
        }
    }
};