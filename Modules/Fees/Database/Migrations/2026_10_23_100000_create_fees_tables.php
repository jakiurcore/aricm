<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every amount is an unsigned integer of poisha (৳1 = 100).
     */
    public function up(): void
    {
        Schema::create('fee_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->string('type', 20);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->restrictOnDelete();
            $table->string('residency_type', 20)->nullable();
            // "all" for the generic row, otherwise the residency type: a plain column so one unique index works with NULLs.
            $table->string('scope', 20)->default('all');
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->unique(['session_id', 'class_id', 'fee_head_id', 'scope'], 'fee_structures_unique');
        });

        Schema::create('exam_fee_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'class_id']);
        });

        Schema::create('student_concessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // null = applies to every head
            $table->foreignId('fee_head_id')->nullable()->constrained('fee_heads')->restrictOnDelete();
            $table->string('kind', 10);
            // percent: 1–100; fixed: poisha
            $table->unsignedBigInteger('value');
            $table->string('reason_type', 20);
            $table->text('note')->nullable();
            $table->date('valid_from_month');
            $table->date('valid_to_month')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fee_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last')->default(0);

            $table->unique(['name', 'year']);
        });

        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 30)->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->date('billing_month')->nullable();
            $table->foreignId('exam_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('late_fine')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('paid')->default(0);
            $table->unsignedBigInteger('due')->default(0);
            $table->string('status', 20)->default('unpaid')->index();
            $table->date('due_date')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('waive_reason')->nullable();
            $table->timestamps();

            // Monthly generation is idempotent: one invoice per student, month and type.
            $table->unique(['student_id', 'billing_month', 'type'], 'fee_invoices_month_unique');
            $table->unique(['student_id', 'exam_id', 'type'], 'fee_invoices_exam_unique');
            $table->index(['student_id', 'status']);
        });

        Schema::create('fee_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->restrictOnDelete();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('net');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 30)->nullable()->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 20);
            $table->string('channel', 20);
            $table->string('transaction_id', 60)->nullable();
            $table->string('sender_number', 20)->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->string('gateway', 30)->nullable();
            $table->json('gateway_payload')->nullable();
            // The invoices the payer chose, kept while the payment is pending; allocation happens on approval.
            $table->json('invoice_ids')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->timestamps();

            // The same wallet/bank transaction id can never be used twice.
            $table->unique(['method', 'transaction_id'], 'payments_txn_unique');
            $table->index(['student_id', 'status']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            // null when the money came from the student's credit balance
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained('fee_invoices')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('source', 10)->default('payment');
            $table->timestamps();

            $table->index('invoice_id');
        });

        Schema::create('student_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['student_credits', 'payment_allocations', 'payments', 'fee_invoice_items', 'fee_invoices', 'fee_sequences', 'student_concessions', 'exam_fee_amounts', 'fee_structures', 'fee_heads'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
