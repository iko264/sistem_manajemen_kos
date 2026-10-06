<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenancy_id')->constrained('tenancies');
            $table->string('period', 7); // 'YYYY-MM'
            $table->unsignedInteger('amount');
            $table->date('due_date');
            $table->enum('status', ['unpaid', 'pending', 'paid'])->default('unpaid');
            $table->timestamps();

            $table->unique(['tenancy_id', 'period']);
            $table->index(['status', 'due_date']); // untuk hitung overdue
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
