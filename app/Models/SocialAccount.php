<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    
    // ponytail: provider_token dipersist oleh AuthController tapi kolom belum
    // dimigrasi — ditulis tanpa error sejak guarded=[]. Fillable ini menegaskan
    // skema DB yang ada. Upgrade: migrasi add provider_token.
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
