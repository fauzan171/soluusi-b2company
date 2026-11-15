<?php

namespace App\Models\Klinik;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KlinikAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'klinik_id',
        'user_id',
        'role',
    ];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
