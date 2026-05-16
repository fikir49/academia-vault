<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvertedIndex;
use Smalot\PdfParser\Parser;

class IndexTechnicalDocuments extends Command
{
    protected $signature = 'vault:index';
    protected $description = 'Extracts technical definitions from PDFs or Text into the Inverted Index';

    public function handle()
    {
        $fileName = 'sample_tech.pdf'; 
        $path = storage_path('app/public/' . $fileName);
        $text = "";

        if (!file_exists($path)) {
            $this->error("File not found at: " . $path);
            return;
        }

        // 1. Extraction Layer: Handle PDF vs Text
        if (str_ends_with($fileName, '.pdf')) {
            $parser = new Parser();
            $text = $parser->parseFile($path)->getText();
        } else {
            $text = file_get_contents($path);
        }

        $this->info("Processing: " . $fileName);

        // 2. Pre-processing: Normalization
        $cleanText = strtolower($text);
        $words = str_word_count($cleanText, 1);
        
        // Simple English Stopwords (You can expand this for Amharic later)
        $stopwords = ['is', 'the', 'and', 'for', 'a', 'to', 'in', 'on', 'with'];
        $filteredWords = array_diff($words, $stopwords);

        // 3. Logic Layer: Populate the Inverted Index with Smart Context
        $wordCounts = array_count_values($filteredWords);

        foreach ($wordCounts as $term => $count) {
            // REGEX: Finds the sentence containing the word to use as a technical definition
            $pattern = '/([^.!?]*\b' . preg_quote($term, '/') . '\b[^.!?]*[.!?])/i';
            preg_match($pattern, $text, $matches);
            
            // If no sentence is found, use a fallback snippet
            $snippet = isset($matches[0]) ? trim($matches[0]) : "Technical context for $term found in $fileName";

            InvertedIndex::updateOrCreate(
                ['term' => $term, 'doc_id' => $fileName],
                [
                    'tf_score' => $count,
                    // iconv handles special characters to prevent database crashes
                    'technical_context' => iconv('UTF-8', 'UTF-8//IGNORE', substr($snippet, 0, 250))
                ]
            );
        }

        $this->info("Success! " . count($wordCounts) . " terms indexed with technical definitions.");
    }
}