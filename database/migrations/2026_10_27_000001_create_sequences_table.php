<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Last number handed out per code prefix (P, INQ, FU, PR, INV, …), so a deleted code is never reused.
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('prefix', 10)->primary();
            $table->unsignedInteger('last')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
