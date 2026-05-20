<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            // Normalize incoming query to lowercase
            $query = mb_strtolower(trim($request->query('query', '')), 'UTF-8');
            $userDept = $request->header('X-Department', 'Information Systems');

            if (empty($query)) {
                return response()->json(['status' => 'success', 'vault_results' => []]);
            }

            // Force lowercase evaluation on the database column for true case-insensitivity
            $results = DB::table('inverted_indices')
                ->whereRaw('LOWER(term) LIKE ?', ['%' . $query . '%'])
                ->get();

            $formatted = $results->map(function ($item) use ($userDept) {
                $isPriority = str_contains(strtolower($item->doc_id ?? ''), 'sample') || 
                              str_contains(strtolower($item->doc_id ?? ''), strtolower($userDept));

                return [
                    'source_pdf' => $item->doc_id ?? 'sample_tech.pdf',
                    'definition' => $item->technical_context ?? 'No context available',
                    'relevance_score' => $item->tf_score ?? 1,
                    'is_priority' => $isPriority,
                    'rank' => $isPriority ? (($item->tf_score ?? 1) + 100) : ($item->tf_score ?? 1)
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
