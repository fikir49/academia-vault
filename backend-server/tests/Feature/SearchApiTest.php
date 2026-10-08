<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ComprehensiveBilingualDictionarySeeder::class);
    }

    public function test_search_endpoint_returns_translated_results(): void
    {
        $response = $this->postJson('/api/search', [
            'query' => 'ስለተዛማጅነቶች',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('query_pipeline.terms.0.english_translation', 'relevance');
    }
}