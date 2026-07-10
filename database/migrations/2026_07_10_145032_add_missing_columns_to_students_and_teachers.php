<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('parent_name')->nullable()->after('grade');
            $table->string('parent_email')->nullable()->after('parent_phone_2');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['parent_name', 'parent_email']);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone']);
        });
    }
};
