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
    Schema::table('users', function (Blueprint $table) {
        // We are adding these 3 columns to the existing users table
        $table->string('deterministic_hash')->unique()->nullable(); 
        $table->string('dept')->default('IS'); // Default to your major!
        $table->integer('year')->nullable();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        // This is the "Undo" button
        $table->dropColumn(['deterministic_hash', 'dept', 'year']);
    });
}
};
