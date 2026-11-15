<?php

namespace App\Models\Perusahaan;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerusahaanAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'perusahaan_id',
        'user_id',
        'role',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
