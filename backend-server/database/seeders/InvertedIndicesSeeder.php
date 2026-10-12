<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvertedIndicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('inverted_indices')->delete();

        // Populate realistic extracted terms from multiple academic PDFs
        DB::table('inverted_indices')->insert([
            [
                'doc_id' => 'Information_Systems_Chapter1.pdf',
                'term' => 'relevance',
                'technical_context' => 'Relevance score in IR measures how accurately a retrieved document fulfills the user query requirements.',
                'tf_score' => 2.45,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doc_id' => 'Database_Systems_Overview.pdf',
                'term' => 'database',
                'technical_context' => 'A structured set of data held in a computer, especially one that is accessible in various ways.',
                'tf_score' => 1.85,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doc_id' => 'Software_Engineering_Principles.pdf',
                'term' => 'architecture',
                'technical_context' => 'Software architecture defines the high-level structure of software systems and the discipline of creating such structures.',
                'tf_score' => 3.12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doc_id' => 'Network_Security_Fundamentals.pdf',
                'term' => 'encryption',
                'technical_context' => 'Encryption is the process of converting information or data into a code, especially to prevent unauthorized access.',
                'tf_score' => 2.90,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // Sync into SQLite FTS5 table
        DB::statement("DELETE FROM fts_inverted_indices;");
        DB::statement("
            INSERT INTO fts_inverted_indices (doc_id, term, technical_context) 
            SELECT doc_id, term, technical_context FROM inverted_indices;
        ");
    }
}