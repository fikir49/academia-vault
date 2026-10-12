<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbacSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ComprehensiveBilingualDictionarySeeder::class);
    }

    public function test_search_returns_access_scope_metadata(): void
    {
        $response = $this->postJson('/api/search/cross-lingual', [
            'query' => 'ስለተዛማጅነቶች',
        ], [
            'X-Department' => 'Information Systems',
            'X-Clearance-Level' => '2',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('access_scope.department', 'Information Systems')
            ->assertJsonPath('access_scope.clearance_level', 2);
    }
}