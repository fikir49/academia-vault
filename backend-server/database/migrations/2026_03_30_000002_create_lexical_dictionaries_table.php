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
        Schema::create('lexical_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('word')->index();
            $table->string('language_code', 5)->default('en')->index(); // 'en', 'am'
            $table->text('generic_definition')->nullable();
            $table->text('synonyms')->nullable(); // JSON or comma-separated terms
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lexical_dictionaries');
    }
};