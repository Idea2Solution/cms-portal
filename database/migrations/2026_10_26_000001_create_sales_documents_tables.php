<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Proposals and invoices share the line-item shape; totals are stored so lists and KPIs can sum them in SQL.
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent', 100)->nullable()->index();
            $table->string('status', 30)->default('Draft')->index();
            $table->date('date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('currency', 3);
            $table->json('items');
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_pct', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('project')->nullable();
            $table->string('agent', 100)->nullable()->index();
            $table->string('state', 20)->default('Issued')->index();
            $table->date('date')->nullable();
            $table->date('due')->nullable();
            $table->string('currency', 3);
            $table->json('items');
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_pct', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('Recorded')->index();
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        // Expressions of interest and requests for quotation, with attached files
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('type', 10)->index();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 10)->default('Received');
            $table->date('date')->nullable();
            $table->string('ref')->nullable();
            $table->string('status', 20)->default('Open');
            $table->text('notes')->nullable();
            $table->json('files')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('proposals');
    }
};
