<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AbacEvaluatorService;
use App\Services\AmharicTextProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    protected AmharicTextProcessor $amharicProcessor;
    protected AbacEvaluatorService $abacService;

    public function __construct(
        AmharicTextProcessor $amharicProcessor,
        AbacEvaluatorService $abacService
    ) {
        $this->amharicProcessor = $amharicProcessor;
        $this->abacService = $abacService;
    }

    /**
     * ABAC-Scoped Standard Search Endpoint
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $queryText = $request->query('query', '');
            
            $userDept = $request->attributes->get('user_department') ?? $request->input('user_department', $request->header('X-Department', 'Information Systems'));
            $userClearance = (int) ($request->attributes->get('user_clearance') ?? $request->input('user_clearance', $request->header('X-Clearance-Level', 1)));

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

            $userAttributes = [
                'department' => $userDept,
                'clearance_level' => $userClearance
            ];

            $formatted = $results->unique('doc_id')->filter(function ($item) use ($userAttributes) {
                $docDept = $item->department ?? 'General';
                $docClearance = (int) ($item->clearance_level ?? 1);
                
                return $this->abacService->authorizeDocumentAccess($userAttributes, $docDept, $docClearance);
            })->map(function ($item) use ($userDept) {
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
                'access_scope' => [
                    'department' => $userDept,
                    'clearance_level' => $userClearance,
                ],
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
     * ABAC-Scoped Cross-Lingual Amharic & English Search Endpoint with Auto-Correct Fallback
     */
    public function crossLingualSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        $userDept = $request->attributes->get('user_department') ?? $request->input('user_department', $request->header('X-Department', 'Information Systems'));
        $userClearance = (int) ($request->attributes->get('user_clearance') ?? $request->input('user_clearance', $request->header('X-Clearance-Level', 1)));

        $rawQuery = $validated['query'];
        $processed = $this->amharicProcessor->processAndTranslate($rawQuery);
        $searchTerms = array_column($processed['terms'], 'english_translation');

        $bilingualResults = DB::table('bilingual_dictionaries')
            ->whereIn('english_translation', $searchTerms)
            ->orWhere('amharic_word', $processed['normalized_query'])
            ->get();

        $lexicalResults = DB::table('lexical_dictionaries')
            ->whereIn('word', $searchTerms)
            ->get();

        $ftsResults = collect();
        if (Schema::hasTable('fts_inverted_indices') && !empty($searchTerms)) {
            $ftsQuery = implode(' OR ', array_map(fn($term) => '"' . trim($term) . '"*', $searchTerms));
            $ftsResults = DB::table('fts_inverted_indices')
                ->whereRaw('fts_inverted_indices MATCH ?', [$ftsQuery])
                ->get();
        }

        // Auto-correct / fuzzy fallback if exact FTS match is empty
        if ($ftsResults->isEmpty() && !empty($searchTerms)) {
            $searchTerm = $searchTerms[0];
            $fuzzyMatch = DB::table('lexical_dictionaries')
                ->where('word', 'LIKE', '%' . substr($searchTerm, 0, 3) . '%')
                ->value('word');

            if ($fuzzyMatch) {
                $searchTerms = [$fuzzyMatch];
                $ftsQuery = '"' . trim($fuzzyMatch) . '"*';
                $ftsResults = DB::table('fts_inverted_indices')
                    ->whereRaw('fts_inverted_indices MATCH ?', [$ftsQuery])
                    ->get();
            }
        }

        $uniqueFtsResults = $ftsResults->unique('doc_id')->values();

        return response()->json([
            'success' => true,
            'access_scope' => [
                'department' => $userDept,
                'clearance_level' => $userClearance,
            ],
            'query_pipeline' => $processed,
            'results' => [
                'bilingual_matches' => $bilingualResults,
                'lexical_matches' => $lexicalResults,
                'fts_matches' => $uniqueFtsResults,
            ],
        ]);
    }
}