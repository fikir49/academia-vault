<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add structural attributes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vault_id')->unique()->nullable()->after('id');
            $table->string('university_id')->unique()->nullable()->after('vault_id');
            $table->string('department')->nullable()->after('university_id');
            $table->string('sex', 10)->nullable()->after('department'); 
            $table->integer('age')->nullable()->after('sex');
        });
    }

    /**
     * Reverse the migrations if rolled back.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vault_id', 'university_id', 'department', 'sex', 'age']);
        });
    }
};