<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create SQLite FTS5 Virtual Table
        DB::statement("
            CREATE VIRTUAL TABLE IF NOT EXISTS fts_inverted_indices USING fts5(
                doc_id UNINDEXED,
                term,
                technical_context,
                tokenize = 'unicode61 remove_diacritics 1'
            );
        ");

        // 2. Safely populate FTS5 table only if inverted_indices already exists
        if (Schema::hasTable('inverted_indices')) {
            DB::statement("
                INSERT INTO fts_inverted_indices (doc_id, term, technical_context)
                SELECT doc_id, term, technical_context FROM inverted_indices;
            ");
        }

        // 3. Create Sync Triggers
        DB::statement("
            CREATE TRIGGER IF NOT EXISTS trg_inverted_indices_ai AFTER INSERT ON inverted_indices BEGIN
                INSERT INTO fts_inverted_indices (doc_id, term, technical_context)
                VALUES (new.doc_id, new.term, new.technical_context);
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS trg_inverted_indices_ad AFTER DELETE ON inverted_indices BEGIN
                DELETE FROM fts_inverted_indices WHERE doc_id = old.doc_id AND term = old.term;
            END;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS trg_inverted_indices_ai;");
        DB::statement("DROP TRIGGER IF EXISTS trg_inverted_indices_ad;");
        DB::statement("DROP TABLE IF EXISTS fts_inverted_indices;");
    }
};