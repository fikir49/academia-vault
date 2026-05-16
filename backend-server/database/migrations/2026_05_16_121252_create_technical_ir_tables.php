<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    // The "Map" of every technical word found in your PDFs
    Schema::create('inverted_indices', function (Blueprint $table) {
        $table->id();
        $table->string('term')->index();        // The word (e.g., "PHP", "Laravel")
        $table->string('doc_id');               // Address of the PDF in the decentralized bin
        $table->integer('tf_score')->default(0); // Term Frequency for ranking [cite: 39]
        $table->text('technical_context');      // The actual definition pulled from the PDF
        $table->timestamps();
    });

    // The "Lexical" layer for standard Oxford/Amharic definitions
    Schema::create('lexical_dictionary', function (Blueprint $table) {
        $table->id();
        $table->string('word')->unique();
        $table->text('definition'); 
        $table->string('language_code', 5); // 'en' or 'am' [cite: 25]
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inverted_indices');
    }
};
