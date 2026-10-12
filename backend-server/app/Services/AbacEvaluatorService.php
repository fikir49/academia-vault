<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AbacEvaluatorService
{
    /**
     * Retrieve all active claims for a user as a key-value attribute map.
     */
    public function getUserAttributes(int $userId): array
    {
        $claims = DB::table('user_claims')
            ->where('user_id', $userId)
            ->get();

        $attributes = [];
        foreach ($claims as $claim) {
            $attributes[$claim->claim_type] = $claim->claim_value;
        }

        return $attributes;
    }

    /**
     * Evaluate whether a user satisfies access constraints for a given department and clearance level.
     */
    public function authorizeDocumentAccess(array $userAttributes, string $docDepartment, int $requiredClearance = 1): bool
    {
        $userDept = $userAttributes['department'] ?? 'General';
        $userClearance = (int) ($userAttributes['clearance_level'] ?? 1);

        // Department check (matches user department or public general files)
        $departmentMatches = ($docDepartment === 'General' || strcasecmp($userDept, $docDepartment) === 0);

        // Clearance level check
        $clearanceMatches = ($userClearance >= $requiredClearance);

        return $departmentMatches && $clearanceMatches;
    }
}