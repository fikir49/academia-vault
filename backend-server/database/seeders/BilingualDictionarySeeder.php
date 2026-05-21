<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BilingualDictionarySeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            ['amharic_term' => 'መረጃ', 'english_translation' => 'data', 'category' => 'Database'],
            ['amharic_term' => 'መረጃጋን', 'english_translation' => 'database', 'category' => 'Database'],
            ['amharic_term' => 'ቁልፍ', 'english_translation' => 'key', 'category' => 'Database'],
            ['amharic_term' => 'ፍለጋ', 'english_translation' => 'search', 'category' => 'Information Retrieval'],
            ['amharic_term' => 'ማውጫ', 'english_translation' => 'index', 'category' => 'Information Retrieval'],
            ['amharic_term' => 'መረብ', 'english_translation' => 'network', 'category' => 'Networking'],
            ['amharic_term' => 'ማስተላለፊያ', 'english_translation' => 'protocol', 'category' => 'Networking'],
            ['amharic_term' => 'አስተናጋጅ', 'english_translation' => 'server', 'category' => 'Networking'],
            ['amharic_term' => 'ድርድር', 'english_translation' => 'array', 'category' => 'Data Structures'],
            ['amharic_term' => 'ክምችት', 'english_translation' => 'stack', 'category' => 'Data Structures'],
            ['amharic_term' => 'ደራሽ', 'english_translation' => 'queue', 'category' => 'Data Structures'],
            ['amharic_term' => 'ውሰብስብ', 'english_translation' => 'complex', 'category' => 'Algorithms'],
            ['amharic_term' => 'ተለዋዋጭ', 'english_translation' => 'dynamic', 'category' => 'Algorithms'],
            ['amharic_term' => 'ፍላተር', 'english_translation' => 'flutter', 'category' => 'Mobile Dev'],
            ['amharic_term' => 'ምዕራፍ', 'english_translation' => 'chapter', 'category' => 'General Academic'],
            ['amharic_term' => 'ተዛማጅነት', 'english_translation' => 'relevance', 'category' => 'Information Retrieval'],
        ];

        DB::table('bilingual_dictionaries')->truncate();
        DB::table('bilingual_dictionaries')->insert($terms);
    }
}
