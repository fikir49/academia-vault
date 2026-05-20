<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        DB::table('inverted_indices')->truncate();
        
        $dataset = [
            ['term' => 'chapter', 'doc_id' => 'IS_core_systems.pdf', 'context' => 'Chapter 1 outlines decentralized system indexing protocols.', 'score' => 4.85],
            ['term' => 'relevance', 'doc_id' => 'Information_Retrieval_Architecture.pdf', 'context' => 'Relevance evaluation rankings determine the prioritization parameters of a vault node.', 'score' => 5.20],
            ['term' => 'dynamic', 'doc_id' => 'Advanced_Data_Structures.pdf', 'context' => 'Dynamic array resizing optimizes algorithmic search boundaries.', 'score' => 3.90],
            ['term' => 'vault', 'doc_id' => 'Academia_Vault_Whitepaper.pdf', 'context' => 'The Academia Vault node encrypts and indexes structured technical definitions.', 'score' => 6.00],
        ];

        foreach ($dataset as $data) {
            DB::table('inverted_indices')->insert([
                'term' => $data['term'],
                'doc_id' => $data['doc_id'],
                'technical_context' => $data['context'],
                'tf_score' => $data['score'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
