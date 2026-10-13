<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AmharicTextProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AmharicTextProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected AmharicTextProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ComprehensiveBilingualDictionarySeeder::class);
        $this->processor = new AmharicTextProcessor();
    }

    public function test_normalizes_amharic_homophones(): void
    {
        // 'ሐ' should map to 'ሀ'
        $normalized = $this->processor->normalize('ሐገር');
        $this->assertEquals('ሀገር', $normalized);
    }

    public function test_stems_amharic_affixes(): void
    {
        $stemmed = $this->processor->stem('ስለተዛማጅነቶች');
        $this->assertEquals('ተዛማጅ', $stemmed);
    }

    public function test_stems_compound_clitic_and_plural_allomorph(): void
    {
        $this->assertSame('ተዛማጅ', $this->processor->stem('ስለተዛማጆች'));
    }

    public function test_stems_multiple_suffix_layers(): void
    {
        $this->assertSame('ተማሪ', $this->processor->stem('ተማሪዎችን'));
        $this->assertSame('መረጃ', $this->processor->stem('የመረጃዎች'));
    }

    public function test_translates_affixed_amharic_word_to_english(): void
    {
        $result = $this->processor->processAndTranslate('ስለተዛማጅነቶች');
        
        $this->assertEquals('relevance', $result['terms'][0]['english_translation']);
    }

    public function test_translates_compound_clitic_query_to_english(): void
    {
        $result = $this->processor->processAndTranslate('ስለተዛማጆች');

        $this->assertSame('ተዛማጅ', $result['terms'][0]['stemmed']);
        $this->assertSame('relevance', $result['terms'][0]['english_translation']);
    }
}