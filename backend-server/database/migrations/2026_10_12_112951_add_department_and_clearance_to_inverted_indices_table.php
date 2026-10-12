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
        Schema::table('inverted_indices', function (Blueprint $table) {
            if (!Schema::hasColumn('inverted_indices', 'department')) {
                $table->string('department')->default('Information Systems')->after('tf_score');
            }
            if (!Schema::hasColumn('inverted_indices', 'clearance_level')) {
                $table->integer('clearance_level')->default(1)->after('department');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inverted_indices', function (Blueprint $table) {
            $table->dropColumn(['department', 'clearance_level']);
        });
    }
};