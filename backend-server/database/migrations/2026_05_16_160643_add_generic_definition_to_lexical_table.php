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
    if (Schema::hasTable('lexical_dictionary')) {
        Schema::table('lexical_dictionary', function (Blueprint $table) {
            $table->text('generic_definition')->nullable(); // The "Oxford" style meaning
            $table->string('synonym')->nullable();           // The "Alternative" English word
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('lexical_dictionary')) {
            Schema::table('lexical_dictionary', function (Blueprint $table) {
                //
            });
        }
    }
};
