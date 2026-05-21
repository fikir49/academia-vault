<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TextProcessor
{
    public static function tokenize($text)
    {
        if (empty(trim($text))) {
            return [];
        }

        $cleaned = trim($text);
        $cleaned = str_replace('\\', '', $cleaned);
        $cleaned = mb_strtolower($cleaned, 'UTF-8');

        $rawTokens = preg_split('/[\s,-]+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        $processedTokens = [];

        foreach ($rawTokens as $token) {
            $trimmedToken = trim($token);
            
            // Explicitly force the absolute connection parameter at runtime to avoid relative path bugs
            config(['database.connections.sqlite.database' => '/workspaces/academia-vault/backend-server/database/database.sqlite']);

            $translation = DB::connection('sqlite')
                ->table('bilingual_dictionaries')
                ->whereRaw('LOWER(amharic_term) LIKE ?', ['%' . $trimmedToken . '%'])
                ->first();

            if ($translation) {
                Log::info("Cross-Lingual Match Found: [{$trimmedToken}] mapped to [{$translation->english_translation}]");
                $processedTokens[] = $translation->english_translation;
            } else {
                $processedTokens[] = $trimmedToken;
            }
        }

        return $processedTokens;
    }
}
