<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveBilingualDictionarySeeder extends Seeder
{
    /**
     * Seed the canonical bilingual and lexical dictionaries.
     */
    public function run(): void
    {
        // 1. Truncate existing tables cleanly
        DB::table('bilingual_dictionaries')->truncate();
        DB::table('lexical_dictionaries')->truncate();

        // 2. Insert canonical Bilingual Mappings (Amharic -> English)
        $bilingualEntries = [
            // Core System & Academic Terms
            ['amharic_word' => 'ተዛማጅነት', 'english_translation' => 'relevance', 'category' => 'academic', 'confidence_score' => 1.00],
            ['amharic_word' => 'መረጃ', 'english_translation' => 'data', 'category' => 'IS', 'confidence_score' => 1.00],
            ['amharic_word' => 'መረጃ', 'english_translation' => 'information', 'category' => 'general', 'confidence_score' => 0.90],
            ['amharic_word' => 'ምዕራፍ', 'english_translation' => 'chapter', 'category' => 'academic', 'confidence_score' => 1.00],
            ['amharic_word' => 'ቀጣይነት', 'english_translation' => 'dynamic', 'category' => 'general', 'confidence_score' => 0.85],
            ['amharic_word' => 'ግምጃ ቤት', 'english_translation' => 'vault', 'category' => 'system', 'confidence_score' => 1.00],
            ['amharic_word' => 'ኮምፒውተር', 'english_translation' => 'computer', 'category' => 'CS', 'confidence_score' => 1.00],
            ['amharic_word' => 'መረብ', 'english_translation' => 'network', 'category' => 'CS', 'confidence_score' => 0.95],
            ['amharic_word' => 'ስርዓት', 'english_translation' => 'system', 'category' => 'IS', 'confidence_score' => 1.00],
            ['amharic_word' => 'ትምህርት', 'english_translation' => 'education', 'category' => 'academic', 'confidence_score' => 1.00],
        ];

        foreach ($bilingualEntries as $entry) {
            DB::table('bilingual_dictionaries')->updateOrInsert(
                [
                    'amharic_word' => $entry['amharic_word'],
                    'english_translation' => $entry['english_translation']
                ],
                array_merge($entry, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 3. Insert Canonical English Lexical Definitions
        $lexicalEntries = [
            [
                'word' => 'relevance',
                'language_code' => 'en',
                'generic_definition' => 'The degree to which a retrieved document satisfies the information need of the user.',
                'synonyms' => json_encode(['applicability', 'pertinence', 'connection'])
            ],
            [
                'word' => 'data',
                'language_code' => 'en',
                'generic_definition' => 'Quantities, characters, or symbols on which operations are performed by a computer.',
                'synonyms' => json_encode(['facts', 'figures', 'statistics', 'inputs'])
            ],
            [
                'word' => 'chapter',
                'language_code' => 'en',
                'generic_definition' => 'A main division of a book or document, typically bearing a number or title.',
                'synonyms' => json_encode(['section', 'part', 'division'])
            ],
            [
                'word' => 'vault',
                'language_code' => 'en',
                'generic_definition' => 'A secure repository for storing and retrieving confidential or distributed resources.',
                'synonyms' => json_encode(['repository', 'archive', 'storehouse'])
            ]
        ];

        foreach ($lexicalEntries as $entry) {
            DB::table('lexical_dictionaries')->updateOrInsert(
                ['word' => $entry['word'], 'language_code' => $entry['language_code']],
                array_merge($entry, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}