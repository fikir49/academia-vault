<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('inverted_indices');
        Schema::create('inverted_indices', function (Blueprint $table) {
            $table->id();
            $table->string('term')->index();
            $table->string('doc_id');
            $table->text('technical_context');
            $table->float('tf_score');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('inverted_indices');
    }
};
