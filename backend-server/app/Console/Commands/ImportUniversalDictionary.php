<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:import-universal-dictionary')]
#[Description('Command description')]
class ImportUniversalDictionary extends Command
{
    /**
     * Execute the console command.
     */
   public function handle()
{
    // In a real-world scenario, you would point this to a large CSV file
    // For now, we use a "Chunking" approach to save RAM
    $this->info("Starting Mass Lexical Import...");

    $data = [
        ['word' => 'computer', 'synonym' => 'computer', 'generic' => 'An electronic device for storing and processing data.', 'lang' => 'en'],
        ['word' => 'ኮምፒውተር', 'synonym' => 'computer', 'generic' => 'መረጃን ለማከማቸት እና ለማቀነባበር የሚያገለግል የኤሌክትሮኒክስ መሣሪያ።', 'lang' => 'am'],
        // Imagine 10,000+ rows here...
    ];

    foreach (array_chunk($data, 100) as $chunk) {
        foreach ($chunk as $item) {
            \App\Models\LexicalDictionary::updateOrCreate(
                ['word' => $item['word']],
                [
                    'synonym' => $item['synonym'],
                    'generic_definition' => $item['generic'],
                    'language_code' => $item['lang']
                ]
            );
        }
    }
    $this->info("Dictionary Loaded into the Local Vault.");
}
}
