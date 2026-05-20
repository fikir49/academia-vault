namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            // 1. Prepare Query (Force lowercase to match Tinker output)
            $query = mb_strtolower(trim($request->query('query')), 'UTF-8');
            $userDept = $request->header('X-Department', 'Information Systems');

            if (empty($query)) return response()->json(['error' => 'Empty search'], 400);

            // 2. Technical Search using the PLURAL 'inverted_indices'
            $results = DB::table('inverted_indices')
                ->where('term', 'LIKE', '%' . $query . '%')
                ->get();

            // 3. Departmental Ranking Logic
            $formatted = $results->map(function ($item) use ($userDept) {
                // If PDF title contains "IS" or "Systems", boost it
                $isPriority = str_contains(strtolower($item->doc_id), 'sample') || 
                              str_contains(strtolower($item->doc_id), strtolower($userDept));

                return [
                    'source_pdf' => $item->doc_id,
                    'definition' => $item->technical_context,
                    'relevance_score' => $item->tf_score,
                    'is_priority' => $isPriority,
                    'rank' => $isPriority ? ($item->tf_score + 100) : $item->tf_score
                ];
            })->sortByDesc('rank')->values();

            return response()->json([
                'status' => 'success',
                'user_context' => "Node specialized for $userDept",
                'vault_results' => $formatted,
                'debug_query' => $query
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Database Error', 'message' => $e->getMessage()], 500);
        }
    }
}