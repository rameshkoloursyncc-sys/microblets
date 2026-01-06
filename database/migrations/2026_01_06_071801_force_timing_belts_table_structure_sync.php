<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Force production timing_belts table to match local structure exactly
     */
    public function up(): void
    {
        // First, create the new table with the correct structure
        Schema::create('timing_belts_new', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20); // Updated to 20 chars for NEOPRENE-XL etc
            $table->string('size', 20); // Belt size
            // Note: NO category column in local structure
            
            // Commercial fields
            $table->string('type', 50)->nullable(); // 1 = FULL SLEEVE, 2 = HALF SLEEVE (for Commercial)
            $table->decimal('mm', 10, 2)->nullable(); // Individual piece length in mm (for Commercial)
            $table->decimal('total_mm', 12, 2)->nullable(); // Total inventory in mm (for Commercial)
            $table->decimal('in_mm', 12, 2)->default(0); // IN mm tracking (for Commercial)
            $table->decimal('out_mm', 12, 2)->default(0); // OUT mm tracking (for Commercial)
            
            // Neoprene fields
            $table->integer('full_sleeve')->nullable(); // Number of full sleeves (for Neoprene)
            $table->integer('in_sleeve')->default(0); // IN sleeve tracking (for Neoprene)
            $table->integer('out_sleeve')->default(0); // OUT sleeve tracking (for Neoprene)
            $table->decimal('rate_per_sleeve', 10, 2)->nullable(); // Rate per sleeve (for Neoprene)
            
            // Common fields
            $table->integer('reorder_level')->nullable(); // Changed to nullable
            $table->decimal('rate', 10, 2)->default(0); // Rate per mm (for Commercial) or rate per sleeve (for Neoprene)
            $table->decimal('value', 10, 2)->default(0); // This is the column we need (not total_value)
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // Changed to match seeding
            $table->unsignedBigInteger('updated_by')->nullable(); // Changed to match seeding
            $table->timestamps();
            
            // Indexes
            $table->index('section');
            $table->index(['total_mm', 'full_sleeve']);
            $table->unique(['section', 'size', 'type']);
        });

        // Copy data from old table to new table, mapping columns appropriately
        if (Schema::hasTable('timing_belts')) {
            $oldData = DB::table('timing_belts')->get();
            
            foreach ($oldData as $row) {
                $newRow = [
                    'id' => $row->id,
                    'section' => $row->section,
                    'size' => $row->size,
                    'type' => $row->type ?? '0',
                    'mm' => $row->mm ?? null,
                    'total_mm' => $row->total_mm ?? 0,
                    'in_mm' => $row->in_mm ?? 0,
                    'out_mm' => $row->out_mm ?? 0,
                    'full_sleeve' => $row->full_sleeve ?? null,
                    'in_sleeve' => $row->in_sleeve ?? 0,
                    'out_sleeve' => $row->out_sleeve ?? 0,
                    'rate_per_sleeve' => $row->rate_per_sleeve ?? null,
                    'reorder_level' => $row->reorder_level ?? null,
                    'rate' => $row->rate ?? 0,
                    'value' => $row->total_value ?? $row->value ?? 0, // Map total_value to value
                    'remark' => $row->remark ?? null,
                    'created_by' => $row->created_by ?? null,
                    'updated_by' => $row->updated_by ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ];
                
                DB::table('timing_belts_new')->insert($newRow);
            }
        }

        // Drop the old table and rename the new one
        Schema::dropIfExists('timing_belts');
        Schema::rename('timing_belts_new', 'timing_belts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive operation, so we'll just recreate the original structure
        Schema::dropIfExists('timing_belts');
        
        Schema::create('timing_belts', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20);
            $table->string('size', 20);
            $table->string('category', 20); // Add back category for rollback
            $table->string('type', 50)->nullable();
            $table->decimal('mm', 10, 2)->nullable();
            $table->decimal('total_mm', 12, 2)->nullable();
            $table->decimal('in_mm', 12, 2)->default(0);
            $table->decimal('out_mm', 12, 2)->default(0);
            $table->integer('full_sleeve')->nullable();
            $table->integer('in_sleeve')->default(0);
            $table->integer('out_sleeve')->default(0);
            $table->decimal('rate_per_sleeve', 10, 2)->nullable();
            $table->integer('reorder_level')->default(5);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0); // Use total_value for rollback
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('section');
            $table->index('category');
            $table->index(['total_mm', 'full_sleeve']);
            $table->unique(['section', 'size', 'type', 'category']);
        });
    }
};
