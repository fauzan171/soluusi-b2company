<?php

namespace App\Models\Perusahaan;

use App\Models\Consultant;
use App\Models\Departement;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerusahaanSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function employee()
    {
        return $this->belongsTo(PerusahaanEmployee::class, 'employee_id');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(\App\Models\ChatMessage::class, 'perusahaan_session_id')->orderBy('id');
    }
}
