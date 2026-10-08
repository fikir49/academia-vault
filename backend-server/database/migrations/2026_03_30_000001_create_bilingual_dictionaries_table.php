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
        Schema::create('bilingual_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('amharic_word')->index();
            $table->string('english_translation')->index();
            $table->string('category')->default('general')->index(); // e.g., 'academic', 'IS', 'CS'
            $table->decimal('confidence_score', 3, 2)->default(1.00); // 0.00 to 1.00 relevance weight
            $table->timestamps();

            // Compound index for fast directional lookups
            $table->unique(['amharic_word', 'english_translation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bilingual_dictionaries');
    }
};