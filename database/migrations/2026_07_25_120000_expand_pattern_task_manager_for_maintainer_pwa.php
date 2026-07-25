<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_tasks', 'task_number')) {
                $table->string('task_number')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('booking_tasks', 'category')) {
                $table->string('category')->nullable()->after('task_type');
            }
            if (! Schema::hasColumn('booking_tasks', 'progress')) {
                $table->unsignedTinyInteger('progress')->default(0)->after('status');
            }
            if (! Schema::hasColumn('booking_tasks', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('progress');
            }
            if (! Schema::hasColumn('booking_tasks', 'expected_completion_date')) {
                $table->date('expected_completion_date')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('booking_tasks', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('booking_tasks', 'pictures')) {
                $table->json('pictures')->nullable()->after('description');
            }
            if (! Schema::hasColumn('booking_tasks', 'final_images')) {
                $table->json('final_images')->nullable()->after('completion_notes');
            }
            if (! Schema::hasColumn('booking_tasks', 'invoice_attachment')) {
                $table->string('invoice_attachment')->nullable()->after('attachments');
            }
            if (! Schema::hasColumn('booking_tasks', 'receipt_attachment')) {
                $table->string('receipt_attachment')->nullable()->after('invoice_attachment');
            }
            if (! Schema::hasColumn('booking_tasks', 'warranty_attachment')) {
                $table->string('warranty_attachment')->nullable()->after('receipt_attachment');
            }
            if (! Schema::hasColumn('booking_tasks', 'labor_cost')) {
                $table->decimal('labor_cost', 12, 2)->default(0)->after('warranty_attachment');
            }
            if (! Schema::hasColumn('booking_tasks', 'material_cost')) {
                $table->decimal('material_cost', 12, 2)->default(0)->after('labor_cost');
            }
            if (! Schema::hasColumn('booking_tasks', 'other_expenses')) {
                $table->decimal('other_expenses', 12, 2)->default(0)->after('material_cost');
            }
            if (! Schema::hasColumn('booking_tasks', 'total_cost')) {
                $table->decimal('total_cost', 12, 2)->default(0)->after('other_expenses');
            }
        });

        if (! Schema::hasTable('booking_task_remarks')) {
            Schema::create('booking_task_remarks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_task_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('remark');
                $table->string('status_update')->nullable();
                $table->json('pictures')->nullable();
                $table->timestamps();
                $table->index(['booking_task_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('booking_task_cost_items')) {
            Schema::create('booking_task_cost_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_task_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->string('label');
                $table->string('worker')->nullable();
                $table->decimal('hours', 8, 2)->nullable();
                $table->decimal('rate', 12, 2)->nullable();
                $table->decimal('quantity', 12, 2)->nullable();
                $table->decimal('unit_price', 12, 2)->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_task_cost_items');
        Schema::dropIfExists('booking_task_remarks');
    }
};
