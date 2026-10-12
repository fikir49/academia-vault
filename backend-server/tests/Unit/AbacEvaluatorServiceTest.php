<?php

namespace Tests\Unit;

use App\Services\AbacEvaluatorService;
use Tests\TestCase;

class AbacEvaluatorServiceTest extends TestCase
{
    protected AbacEvaluatorService $abacService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->abacService = new AbacEvaluatorService();
    }

    public function test_grants_access_when_department_and_clearance_match(): void
    {
        $userAttributes = [
            'department' => 'Information Systems',
            'clearance_level' => 2,
        ];

        $authorized = $this->abacService->authorizeDocumentAccess($userAttributes, 'Information Systems', 2);
        $this->assertTrue($authorized);
    }

    public function test_denies_access_when_clearance_is_insufficient(): void
    {
        $userAttributes = [
            'department' => 'Information Systems',
            'clearance_level' => 1,
        ];

        $authorized = $this->abacService->authorizeDocumentAccess($userAttributes, 'Information Systems', 3);
        $this->assertFalse($authorized);
    }
}