<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'tenant'])->default('tenant')->after('password');
            $table->string('phone')->nullable()->after('role');
            $table->string('identity_number')->nullable()->after('phone');
            $table->text('address')->nullable()->after('identity_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'identity_number', 'address']);
        });
    }
};
