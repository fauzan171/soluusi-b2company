<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahAdmin extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
