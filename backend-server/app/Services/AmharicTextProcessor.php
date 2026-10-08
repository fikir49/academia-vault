<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AmharicTextProcessor
{
    /**
     * Common Amharic prefixes for morphological stripping (ordered by length).
     * Note: Standalone 'ተ' is excluded to prevent over-stemming roots like 'ተዛማጅ'.
     */
    protected array $prefixes = [
        'ስለሚሆን', 'እንደሚሆን', 'ከሚከተሉት', 'በሚከተሉት',
        'ስለሚ', 'እንደሚ', 'በየ', 'ከየ', 'ለየ',
        'እየ',
        'ስለ', 'እንደ', 'ከእነ', 'በእነ', 'ለእነ',
        'የ', 'በ', 'ለ', 'ከ', 'ወደ', 'እስከ'
    ];

    /**
     * Common Amharic suffixes for morphological stripping (ordered by length).
     */
    protected array $suffixes = [
        'ዎቻቸውን', 'ዎቻችንን', 'ዎቻችሁን',
        'ዎችን', 'ዎችንም', 'ዎችም', 'ዎቻችን', 'ዎቻችሁ', 'ዎቻቸው',
        'ነቶች', 'ዎች', 'ችን', 'ችሁ', 'ቸው', 'ነት', 'ያዊ', 'ኛ', 'አችን', 'አቸው'
    ];

    public function normalize(string $text): string
    {
        if (empty(trim($text))) {
            return '';
        }

        $homophoneMap = [
            'ሐ' => 'ሀ', 'ሑ' => 'ሁ', 'ሒ' => 'ሂ', 'ሓ' => 'ሃ', 'ሔ' => 'ሄ', 'ሕ' => 'ህ', 'ሖ' => 'ሆ',
            'ኀ' => 'ሀ', 'ኁ' => 'ሁ', 'ኂ' => 'ሂ', 'ኃ' => 'ሃ', 'ኄ' => 'ሄ', 'ኅ' => 'ህ', 'ኆ' => 'ሆ',
            'ሠ' => 'ሰ', 'ሡ' => 'ሱ', 'ሢ' => 'ሲ', 'ሣ' => 'ሳ', 'ሤ' => 'ሴ', 'ሥ' => 'ስ', 'ሦ' => 'ሶ',
            'ፀ' => 'ጸ', 'ፁ' => 'ጹ', 'ፂ' => 'ጺ', 'ፃ' => 'ጻ', 'ፄ' => 'ጼ', 'ፅ' => 'ጽ', 'ፆ' => 'ጾ',
            'ዐ' => 'አ', 'ዑ' => 'ኡ', 'ዒ' => 'ኢ', 'ዓ' => 'ኣ', 'ዔ' => 'ኤ', 'ዕ' => 'እ', 'ዖ' => 'ኦ',
            'ኈ' => 'ሁ', 'ኌ' => 'ሄ', 'ኍ' => 'ሁ',
            'ጐ' => 'ጎ', 'ጒ' => 'ጊ', 'ጓ' => 'ጋ', 'ጔ' => 'ጌ', 'ጕ' => 'ግ'
        ];

        $normalized = strtr($text, $homophoneMap);
        $normalized = preg_replace('/[፡።፤፥፦፧፨፠]/u', ' ', $normalized);
        return trim(preg_replace('/\s+/u', ' ', $normalized));
    }

    /**
     * Iteratively strip prefixes and suffixes until root stabilizes.
     */
    public function stem(string $word): string
    {
        $normalizedWord = $this->normalize($word);

        if (mb_strlen($normalizedWord, 'UTF-8') <= 3) {
            return $normalizedWord;
        }

        $stemmed = $normalizedWord;
        $changed = true;

        while ($changed && mb_strlen($stemmed, 'UTF-8') > 3) {
            $changed = false;

            // Strip Prefixes
            foreach ($this->prefixes as $prefix) {
                if (mb_strpos($stemmed, $prefix, 0, 'UTF-8') === 0) {
                    $candidate = mb_substr($stemmed, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8');
                    if (mb_strlen($candidate, 'UTF-8') >= 3) {
                        $stemmed = $candidate;
                        $changed = true;
                        break;
                    }
                }
            }

            // Strip Suffixes
            foreach ($this->suffixes as $suffix) {
                $suffixLength = mb_strlen($suffix, 'UTF-8');
                $stemmedLength = mb_strlen($stemmed, 'UTF-8');

                if ($stemmedLength > $suffixLength && mb_substr($stemmed, -$suffixLength, null, 'UTF-8') === $suffix) {
                    $candidate = mb_substr($stemmed, 0, $stemmedLength - $suffixLength, 'UTF-8');
                    if (mb_strlen($candidate, 'UTF-8') >= 3) {
                        $stemmed = $candidate;
                        $changed = true;
                        break;
                    }
                }
            }
        }

        return $stemmed;
    }

    public function processAndTranslate(string $amharicQuery): array
    {
        $normalized = $this->normalize($amharicQuery);
        $words = explode(' ', $normalized);
        $translatedTerms = [];

        foreach ($words as $word) {
            if (empty($word)) continue;

            $stemmedWord = $this->stem($word);

            // 1. Try exact match on raw word
            $translation = DB::table('bilingual_dictionaries')
                ->where('amharic_word', $word)
                ->value('english_translation');

            // 2. Fallback to stemmed root
            if (!$translation && $stemmedWord !== $word) {
                $translation = DB::table('bilingual_dictionaries')
                    ->where('amharic_word', $stemmedWord)
                    ->value('english_translation');
            }

            // 3. Fallback to LIKE match on stemmed root
            if (!$translation && $stemmedWord !== $word) {
                $translation = DB::table('bilingual_dictionaries')
                    ->where('amharic_word', 'LIKE', '%' . $stemmedWord . '%')
                    ->value('english_translation');
            }

            $translatedTerms[] = [
                'original' => $word,
                'stemmed' => $stemmedWord,
                'english_translation' => $translation ?? $word
            ];
        }

        return [
            'raw_query' => $amharicQuery,
            'normalized_query' => $normalized,
            'terms' => $translatedTerms,
            'english_search_string' => implode(' ', array_column($translatedTerms, 'english_translation'))
        ];
    }
}