<?php

namespace App\Models\Klinik;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HapPervorma extends Model
{
    use HasFactory;

    protected $table = 'hap_pervormas';

    protected $guarded = ['id'];

    protected $casts = [
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'requested_at' => 'datetime',
    ];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
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
