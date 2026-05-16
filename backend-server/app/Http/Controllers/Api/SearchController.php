<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InvertedIndex;
use App\Models\LexicalDictionary;

class SearchController extends Controller
{
  public function search(Request $request)
{
    $query = strtolower(trim($request->input('query')));
    $userDept = $request->header('X-User-Dept', 'General'); // e.g., "IS" or "Computing"

    // 1. Stopword Filter (Protecting the "Real World" logic)
    $stopwords = ['the', 'is', 'at', 'which', 'on', 'like', 'and', 'a', 'to'];
    if (in_array($query, $stopwords)) {
        return response()->json(['message' => 'Generic term: No technical relevance found.'], 200);
    }

    // 2. Lexical Dictionary Search (Oxford Level)
    $lexical = \App\Models\LexicalDictionary::where('word', $query)->first();

    // 3. Technical Vault Search with Identity Ranking
    $technicalResults = \App\Models\InvertedIndex::where('term', $query)
        ->get()
        ->map(function ($item) use ($userDept) {
            // Logic: If the PDF filename contains the user's department, boost the rank
            $item->priority = str_contains(strtolower($item->doc_id), strtolower($userDept)) ? 1 : 0;
            return $item;
        })
        ->sortByDesc('priority') // Custom student results at the top
        ->values();

    if ($technicalResults->isEmpty() && !$lexical) {
        return response()->json(['message' => 'No match found in the local network.'], 404);
    }

    return response()->json([
        'user_context' => "Personalized for $userDept Student",
        'dictionary' => $lexical ? $lexical->generic_definition : "No lexical match.",
        'vault_results' => $technicalResults->map(function($res) {
            return [
                'source_pdf' => $res->doc_id,
                'definition' => $res->technical_context,
                'relevance_score' => $res->tf_score
            ];
        })
    ]);
}
}