<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'vault_id',
        'university_id',
        'department',
        'sex',
        'age',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The "booted" method of the model.
     * This acts as an internal listener hook for database lifecycle actions.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // 1. Only run this if a custom vault_id hasn't been manually assigned yet
            if (empty($user->vault_id)) {
                
                // 2. Extract context parameters (Fallback to 'GEN' if department is missing)
                $deptCode = strtoupper($user->department ?? 'GEN'); 
                $yearSuffix = date('y'); // Returns '26' for 2026
                
                // 3. Look at the database to find the last registered user from the exact same department and year
                $lastUser = self::where('department', $user->department)
                                ->where('vault_id', 'like', "BDU-{$deptCode}-{$yearSuffix}-%")
                                ->orderBy('id', 'desc')
                                ->first();

                $nextSequence = 1;

                if ($lastUser && $lastUser->vault_id) {
                    // Extract the number from the end of the last vault_id string (e.g., '0049' becomes 49)
                    $parts = explode('-', $lastUser->vault_id);
                    $lastSequenceNumber = (int) end($parts);
                    $nextSequence = $lastSequenceNumber + 1;
                }

                // 4. Pad the number out with zeros so it looks clean (e.g., 5 becomes '0005')
                $paddedSequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

                // 5. Mint the final deterministic identity code string
                $user->vault_id = "BDU-{$deptCode}-{$yearSuffix}-{$paddedSequence}";
            }
        });
    }

    /**
     * Establish a structural relationship with the User Claims table.
     */
    public function claims()
    {
        return $this->hasMany(UserClaim::class);
    }
}