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
        'biometric_mesh',
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
        if (empty($user->vault_id)) {
            // Map known department names to clean shortcodes
            $deptMap = [
                'Information Systems' => 'IS',
                'Computer Science'    => 'CS',
                'Information Technology' => 'IT',
            ];

            $rawDept = $user->department ?? 'GEN';
            $deptCode = $deptMap[$rawDept] ?? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawDept));
            $yearSuffix = date('y');

            $lastUser = self::where('department', $user->department)
                            ->where('vault_id', 'like', "BDU-{$deptCode}-{$yearSuffix}-%")
                            ->orderBy('id', 'desc')
                            ->first();

            $nextSequence = 1;

            if ($lastUser && $lastUser->vault_id) {
                $parts = explode('-', $lastUser->vault_id);
                $lastSequenceNumber = (int) end($parts);
                $nextSequence = $lastSequenceNumber + 1;
            }

            $paddedSequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            $user->vault_id = "BDU-{$deptCode}-{$yearSuffix}-{$paddedSequence}";
        }
    });
}
       public function claims()
    {
        return $this->hasMany(UserClaim::class);
    }
}