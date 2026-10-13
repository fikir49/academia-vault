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
  private function extractSmartDefinition(array $sentences, string $term): ?string
    {
        $bestSentence = null;
        $highestScore = -1.0;
        $lowerTerm = mb_strtolower(trim($term), 'UTF-8');

        // Ignore meaningless single-word queries or stopwords
        $stopwords = ['is', 'are', 'the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'it', 'by', 'of', 'and', 'or'];
        if (in_array($lowerTerm, $stopwords) || mb_strlen($lowerTerm, 'UTF-8') < 2) {
            return null;
        }

        foreach ($sentences as $sentence) {
            $cleanSentence = trim(str_replace(["\r", "\n", "\t"], ' ', $sentence));
            $lowerSentence = mb_strtolower($cleanSentence, 'UTF-8');

            // 1. BLACKLIST FILTER: Skip TOC, headers, cover pages, and short garbage
            if (
                preg_match('/^(part|chapter|section|table of contents|executive summary|introduction|\d+\.)/ui', $cleanSentence) ||
                mb_strlen($cleanSentence, 'UTF-8') < 40 || 
                mb_strlen($cleanSentence, 'UTF-8') > 400
            ) {
                continue;
            }

            // 2. PHRASE MATCHING: The sentence must contain the exact search phrase or major sub-terms
            if (mb_strpos($lowerSentence, $lowerTerm) === false) {
                // If multi-word phrase, check if at least 70% of the significant words are present
                $termWords = array_filter(explode(' ', $lowerTerm), fn($w) => mb_strlen($w) > 3);
                if (count($termWords) > 1) {
                    $matchedCount = 0;
                    foreach ($termWords as $tw) {
                        if (mb_strpos($lowerSentence, $tw) !== false) $matchedCount++;
                    }
                    if (($matchedCount / count($termWords)) < 0.7) {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            // 3. DEFINITIONAL PATTERN SCORING (The "Is it a definition?" test)
            $definitionalScore = 0.0;

            // High confidence definitional triggers (English & Amharic)
            if (preg_match('/\b(is defined as|refers to|means|denotes|consists of|is a type of|represents|ማለት|ነው|ይወክላል|የሚለውን ይገልፃል)\b/ui', $lowerSentence)) {
                $definitionalScore += 2.5;
            }

            // Medium confidence structural proximity (Term appears near the beginning of a explanatory sentence)
            $termPosition = mb_strpos($lowerSentence, $lowerTerm);
            if ($termPosition !== false && $termPosition < 50) {
                $definitionalScore += 1.0;
            }

            // Penalty for question marks or conversational filler
            if (mb_strpos($lowerSentence, '?') !== false) {
                $definitionalScore -= 1.0;
            }

            // If it scores higher than previous candidates, keep it
            if ($definitionalScore > $highestScore) {
                $highestScore = $definitionalScore;
                $bestSentence = $cleanSentence;
            }
        }

        // If no strict definitional sentence found, return null instead of a garbage snippet
        return $highestScore > 0 ? $bestSentence : null;
    }
    }
