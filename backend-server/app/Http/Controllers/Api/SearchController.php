<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AmharicTextProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    protected AmharicTextProcessor $amharicProcessor;

    public function __construct(AmharicTextProcessor $amharicProcessor)
    {
        $this->amharicProcessor = $amharicProcessor;
    }

    /**
     * Inverted Index Search Endpoint
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $queryText = $request->query('query', '');
            $userDept = $request->header('X-Department', 'Information Systems');

            if (empty(trim($queryText))) {
                return response()->json(['status' => 'success', 'vault_results' => []]);
            }

        
$searchTokens = preg_split('/\s+/', mb_strtolower(trim($queryText), 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);

            if (empty($searchTokens)) {
                return response()->json(['status' => 'success', 'vault_results' => []]);
            }

            $targetToken = reset($searchTokens);
            $cleanToken = '%' . mb_strtolower($targetToken, 'UTF-8') . '%';

            $results = DB::table('inverted_indices')
                ->whereRaw('LOWER(term) LIKE ?', [$cleanToken])
                ->orWhereRaw('LOWER(technical_context) LIKE ?', [$cleanToken])
                ->get();

            $formatted = $results->map(function ($item) use ($userDept) {
                $isPriority = str_contains(strtolower($item->doc_id ?? ''), 'sample') || 
                              str_contains(strtolower($item->doc_id ?? ''), strtolower($userDept));

                return [
                    'source_pdf' => $item->doc_id ?? 'sample_tech.pdf',
                    'definition' => $item->technical_context ?? 'No context available',
                    'relevance_score' => $item->tf_score ?? 1.0,
                    'is_priority' => $isPriority,
                    'rank' => $isPriority ? (($item->tf_score ?? 1.0) + 100) : ($item->tf_score ?? 1.0)
                ];
            })->sortByDesc('rank')->values()->all();

            return response()->json([
                'status' => 'success',
                'vault_results' => $formatted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'vault_results' => [],
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cross-Lingual Amharic & English Search Endpoint
     */
    public function crossLingualSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        $rawQuery = $validated['query'];

        $processed = $this->amharicProcessor->processAndTranslate($rawQuery);
        $searchTerms = array_column($processed['terms'], 'english_translation');

        $bilingualResults = DB::table('bilingual_dictionaries')
            ->whereIn('english_translation', $searchTerms)
            ->orWhere('amharic_word', $processed['normalized_query'])
            ->get();

        $lexicalResults = DB::table('lexical_dictionaries')
            ->whereIn('term', $searchTerms)
            ->orWhereIn('amharic_translation', array_column($processed['terms'], 'original'))
            ->get();

        return response()->json([
            'success' => true,
            'query_pipeline' => $processed,
            'results' => [
                'bilingual_matches' => $bilingualResults,
                'lexical_matches' => $lexicalResults,
            ],
        ]);
    }
}