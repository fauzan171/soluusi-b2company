<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'sekolah_id',
        'user_id',
        'role',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
