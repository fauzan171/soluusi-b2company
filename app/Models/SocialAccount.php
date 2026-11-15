<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    // ponytail: skema social_accounts pakai provider_name, tapi AuthController
    // updateOrCreate menulis 'provider' — kolom provider tidak ada, jadi query
    // saat ini hanya match lewat provider_id. Mismatch pre-existing, di luar
    // scope task ini. Upgrade path: rename kolom provider_name → provider atau
    // sesuaikan AuthController.
    protected $fillable = [
        'user_id',
        'provider',
        'provider_name',
        'provider_id',
        'provider_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
