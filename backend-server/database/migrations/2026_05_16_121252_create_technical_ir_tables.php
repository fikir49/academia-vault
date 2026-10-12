<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('inverted_indices');
       Schema::create('inverted_indices', function (Blueprint $table) {
    $table->id();
    $table->string('doc_id');
    $table->string('term');
    $table->text('technical_context');
    $table->float('tf_score')->default(1.0);
    $table->string('department')->default('General');
    $table->integer('clearance_level')->default(1); // Added for local node ABAC
    $table->timestamps();
});
    }
    public function down(): void {
        Schema::dropIfExists('inverted_indices');
    }
};
