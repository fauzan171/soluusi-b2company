<?php

namespace App\Models\Sekolah;

use App\Models\Consultant;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function student()
    {
        return $this->belongsTo(SekolahStudent::class, 'student_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
