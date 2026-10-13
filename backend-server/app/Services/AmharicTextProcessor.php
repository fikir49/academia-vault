<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AmharicTextProcessor
{
    protected array $prefixes = [
        'ስለሚሆን', 'እንደሚሆን', 'ከሚከተሉት', 'በሚከተሉት',
        'ስለሚ', 'እንደሚ', 'በየ', 'ከየ', 'ለየ',
        'እየ',
        'ስለ', 'እንደ', 'ከእነ', 'በእነ', 'ለእነ',
        'የ', 'በ', 'ለ', 'ከ', 'ወደ', 'እስከ'
    ];

    protected array $suffixes = [
        'ዎቻቸውን', 'ዎቻችንን', 'ዎቻችሁን',
        'ዎችንም', 'ዎችን', 'ዎችም', 'ዎቻችን', 'ዎቻችሁ', 'ዎቻቸው',
        'ነቶች', 'ጆች', 'ዎች', 'ኦች', 'ችን', 'ችሁ', 'ቸው', 'ነት', 'ያዊ', 'ኛ', 'አችን', 'አቸው'
    ];

    public function normalize(string $text): string
    {
        if (empty(trim($text))) {
            return '';
        }

        if (!class_exists(\Normalizer::class)) {
            throw new \RuntimeException('The intl PHP extension is required for NFC normalization.');
        }

        $text = \Normalizer::normalize($text, \Normalizer::FORM_C);
        if ($text === false) {
            throw new \InvalidArgumentException('The input could not be NFC normalized.');
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
        return trim((string) preg_replace('/\s+/u', ' ', $normalized));
    }

    public function stem(string $word): string
    {
        $word = $this->normalize($word);

        $changed = true;
        while ($changed && mb_strlen($word, 'UTF-8') > 3) {
            $changed = false;

            foreach ($this->prefixes as $prefix) {
                $prefixLength = mb_strlen($prefix, 'UTF-8');
                if (mb_strpos($word, $prefix, 0, 'UTF-8') === 0
                    && mb_strlen($word, 'UTF-8') - $prefixLength >= 3) {
                    $word = mb_substr($word, $prefixLength, null, 'UTF-8');
                    $changed = true;
                    break;
                }
            }

            foreach ($this->suffixes as $suffix) {
                $suffixLength = mb_strlen($suffix, 'UTF-8');
                $wordLength = mb_strlen($word, 'UTF-8');
                if ($wordLength - $suffixLength < 3
                    || mb_substr($word, -$suffixLength, null, 'UTF-8') !== $suffix) {
                    continue;
                }

                $stem = mb_substr($word, 0, $wordLength - $suffixLength, 'UTF-8');
                $word = $suffix === 'ጆች' ? $stem . 'ጅ' : $stem;
                $changed = true;
                break;
            }
        }

        return trim($word);
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

            // 2. Fallback to stemmed root exact match
            if (!$translation && $stemmedWord !== $word) {
                $translation = DB::table('bilingual_dictionaries')
                    ->where('amharic_word', $stemmedWord)
                    ->value('english_translation');
            }

            // 3. Fallback to wildcard LIKE match on stemmed root
            if (!$translation && $stemmedWord !== $word) {
                $translation = DB::table('bilingual_dictionaries')
                    ->where('amharic_word', 'LIKE', '%' . $stemmedWord . '%')
                    ->value('english_translation');
            }

            $translatedTerms[] = [
                'original' => $word,
                'stemmed' => $stemmedWord,
                'english_translation' => $translation ?? $stemmedWord
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