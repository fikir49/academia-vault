<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TextProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $queryText = $request->query('query', '');
            $userDept = $request->header('X-Department', 'Information Systems');

            if (empty(trim($queryText))) {
                return response()->json(['status' => 'success', 'vault_results' => []]);
            }

            $searchTokens = TextProcessor::tokenize($queryText);

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
}
