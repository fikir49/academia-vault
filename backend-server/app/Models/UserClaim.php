<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserClaim extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'claim_type',
        'claim_value',
    ];

    /**
     * Establish the inverse structural relationship back to the User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}