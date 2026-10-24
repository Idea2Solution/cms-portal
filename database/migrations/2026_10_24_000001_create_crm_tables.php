<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leads and customers live in one table: a lead becomes a customer and keeps its history.
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('type', 20)->default('Prospect')->index();
            $table->string('customer_no', 20)->nullable()->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('contact')->nullable();
            $table->string('designation')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('phone2', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('preferred', 30)->nullable();
            $table->json('socials')->nullable();
            $table->string('source', 50)->nullable();
            $table->string('source_url')->nullable();
            $table->string('agent', 100)->nullable()->index();
            $table->string('priority', 10)->default('Warm')->index();
            $table->string('status', 30)->default('Not Contacted')->index();
            $table->text('notes')->nullable();
            $table->date('last_contacted')->nullable();
            $table->date('converted_at')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->dateTime('date')->nullable();
            $table->string('source', 50)->nullable();
            $table->string('agent', 100)->nullable();
            $table->string('category')->nullable();
            $table->string('service')->nullable();
            $table->text('requirement')->nullable();
            $table->decimal('budget_from', 12, 2)->nullable();
            $table->decimal('budget_to', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->string('priority', 10)->default('Warm');
            $table->string('status', 30)->default('New')->index();
            $table->text('notes')->nullable();
            $table->date('closed_at')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent', 100)->nullable()->index();
            $table->dateTime('due')->index();
            $table->string('method', 30)->default('Phone');
            $table->string('purpose')->nullable();
            $table->string('status', 20)->default('Pending')->index();
            $table->text('outcome')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('Note');
            $table->text('text');
            $table->string('by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value')->nullable(); // text, not json: MySQL JSON reorders object keys
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('remarks');
        Schema::dropIfExists('followups');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('companies');
    }
};
