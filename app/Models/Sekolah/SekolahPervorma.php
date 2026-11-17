<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahPervorma extends Model
{
    use HasFactory;

    protected $table = 'sekolah_pervormas';

    protected $fillable = [
        'sekolah_id',
        'associate_id',
        'stage',
        'amount',
        'status',
        'due_at',
        'paid_at',
        'requested_at',
        'requested_by',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'requested_at' => 'datetime',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function associate()
    {
        return $this->belongsTo(User::class, 'associate_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
