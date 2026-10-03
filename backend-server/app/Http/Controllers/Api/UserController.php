<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        // Simulated authenticated user profile with role, privileges, and badge metadata
        // In production, this will pull from the `users` table and Sanctum token claims.
        
        $role =$request->header('X-User-Role', 'class_rep'); // Default to Class Rep for testing

        $userProfile = [
            'id' => 'usr_99812',
            'name' => 'Fikir wendmnew Kassa',
            'department' => 'Information Systems',
            'role' => $role, // 'student', 'class_rep', 'maintainer', 'admin'
            'privileges' => $this->getPrivilegesForRole($role),
            'badges' => $this->getBadgesForRole($role),
            'theme_config' => [
                'accent_color' => $role === 'class_rep' ? '#4F46E5' : '#0EA5E9',
                'vibe_mode' => 'github_dark',
                'dashboard_layout' => $role === 'class_rep' ? 'rep_management' : 'standard_feed'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $userProfile
        ]);
    }

    private function getPrivilegesForRole(string $role): array
    {
        return match ($role) {
            'admin', 'maintainer' => [
                'can_upload_official_docs' => true,
                'can_pin_announcements' => true,
                'can_verify_peer_projects' => true,
                'can_moderate_vibe_feed' => true,
                'upload_quota_mb' => 5000,
            ],
            'class_rep' => [
                'can_upload_official_docs' => false,
                'can_pin_announcements' => true,
                'can_verify_peer_projects' => true,
                'can_moderate_vibe_feed' => false,
                'upload_quota_mb' => 1000,
            ],
            default => [ // 'student'
                'can_upload_official_docs' => false,
                'can_pin_announcements' => false,
                'can_verify_peer_projects' => false,
                'can_moderate_vibe_feed' => false,
                'upload_quota_mb' => 250,
            ],
        };
    }

    private function getBadgesForRole(string $role): array
    {
        $commonBadges = [
            ['id' => 'is_node', 'label' => 'IS Node', 'icon' => 'terminal', 'color' => '#10B981']
        ];

        if ($role === 'class_rep') {$commonBadges[] = ['id' => 'class_rep', 'label' => 'Class Rep', 'icon' => 'shield_check', 'color' => '#6366F1'];
        } elseif ($role === 'maintainer' || $role === 'admin') {$commonBadges[] = ['id' => 'core_maintainer', 'label' => 'Vault Maintainer', 'icon' => 'code_bracket', 'color' => '#EC4899'];
        }

        return $commonBadges;
    }
}
