<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Smalot\PdfParser\Parser;

class PdfIndexerService
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function indexPdf(string $filePath, string $docId, string $department = 'Information Systems', int $clearanceLevel = 1): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("PDF file not found at path: {$filePath}");
        }

        // 1. Extract raw text from PDF
        $pdf = $this->parser->parseFile($filePath);
        $rawText = $pdf->getText();

        if (empty(trim($rawText))) {
            throw new \Exception("No readable text could be extracted from PDF: {$docId}");
        }

        // 2. Normalize and split text into clean grammatical sentences (Supports Amharic '።' and English '.!?')
        $sentences = preg_split('/(?<=[.?!።])\s+/u', $rawText, -1, PREG_SPLIT_NO_EMPTY);

        // Tokenize for term frequency computation
        $words = preg_split('/[\s,.\/()\-:\'\"]+/u', mb_strtolower($rawText, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
        
        $stopwords = [
            'the', 'and', 'is', 'in', 'at', 'of', 'a', 'an', 'to', 'for', 'with', 'on', 'that', 'this', 'by', 'from',
            'ነው', 'እና', 'ወደ', 'በ', 'ከ', 'ስለ', 'ጋር', 'ለ', 'ይህ', 'ያ'
        ];

        $filteredWords = array_filter($words, function ($word) use ($stopwords) {
            return mb_strlen($word, 'UTF-8') > 3 && !in_array($word, $stopwords);
        });

        $wordCounts = array_count_values($filteredWords);
        $totalWords = max(count($filteredWords), 1);

        // Delete previous records for this document
        DB::table('inverted_indices')->where('doc_id', $docId)->delete();

        $records = [];
        foreach ($wordCounts as $term => $count) {
            if ($count >= 1) {
                $tfScore = round(($count / $totalWords) * 100, 2);

                // 3. INTELLIGENT EDGE EXTRACTOR: Score & Classify Sentences
                $smartDefinition = $this->extractSmartDefinition($sentences, $term);

                $records[] = [
                    'doc_id' => $docId,
                    'term' => $term,
                    'technical_context' => $smartDefinition,
                    'tf_score' => $tfScore,
                    'department' => $department,
                    'clearance_level' => $clearanceLevel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 4. Batch Insert into inverted_indices
        foreach (array_chunk($records, 100) as $chunk) {
            DB::table('inverted_indices')->insert($chunk);
        }

        // 5. Sync into SQLite FTS5 Table
        DB::statement("
            INSERT INTO fts_inverted_indices (doc_id, term, technical_context)
            SELECT doc_id, term, technical_context FROM inverted_indices WHERE doc_id = ?;
        ", [$docId]);

        return [
            'doc_id' => $docId,
            'indexed_terms_count' => count($records),
            'status' => 'success'
        ];
    }

    /**
     * Intelligent Edge Extractor Algorithm
     * Ranks candidate sentences based on term density, sentence structure, and definitional semantic score.
     */
    private function extractSmartDefinition(array $sentences, string $term): string
    {
        $bestSentence = null;
        $highestScore = -1.0;

        foreach ($sentences as $sentence) {
            $cleanSentence = trim(str_replace(["\r", "\n"], ' ', $sentence));
            $lowerSentence = mb_strtolower($cleanSentence, 'UTF-8');

            // Must contain the target term
            if (mb_strpos($lowerSentence, $term) === false) {
                continue;
            }

            // Reject sentences that are too short (e.g., titles, headers) or excessively long garbage text
            $charCount = mb_strlen($cleanSentence, 'UTF-8');
            if ($charCount < 25 || $charCount > 350) {
                continue;
            }

            // Scoring Factor 1: Term Density Score
            $termMatches = mb_substr_count($lowerSentence, $term);
            $densityScore = ($termMatches * mb_strlen($term, 'UTF-8')) / $charCount;

            // Scoring Factor 2: Definitional Structure Indicators (Semantic Signals)
            $semanticBonus = 0.0;
            if (preg_match('/\b(is|are|defined as|refers to|means|denotes|consists of|ማለት|ነው|ይሆናል|ይወክላል)\b/ui', $lowerSentence)) {
                $semanticBonus += 0.5;
            }

            // Scoring Factor 3: Sentence Position Preference (Definitions usually start sentences)
            $positionBonus = 0.0;
            if (mb_strpos($lowerSentence, $term) < 30) {
                $positionBonus += 0.2;
            }

            // Composite Intelligence Score
            $totalScore = $densityScore + $semanticBonus + $positionBonus;

            if ($totalScore > $highestScore) {
                $highestScore = $totalScore;
                $bestSentence = $cleanSentence;
            }
        }

        return $bestSentence ?? "Technical context extracted for {$term}.";
    }
}