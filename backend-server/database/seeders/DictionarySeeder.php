<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DictionarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
{
    \App\Models\LexicalDictionary::create([
        'word' => 'ተዛማጅነት', // Amharic for Relevance
        'definition' => 'relevance', 
        'language_code' => 'am'
    ]);
    
    \App\Models\LexicalDictionary::create([
        'word' => 'መረጃ', // Amharic for Information
        'definition' => 'information',
        'language_code' => 'am'
    ]);
}
}
