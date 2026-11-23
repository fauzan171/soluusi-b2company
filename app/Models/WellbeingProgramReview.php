<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Review kuartalan kontrak Impact (wv2 research2-infra §3): 4 rows dibuat
 * saat assign (transaction wizard), due_at = starts_at + 3×period bulan.
 */
class WellbeingProgramReview extends Model
{
    use HasFactory;

    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_COMPLETED = 'completed';

    protected $guarded = ['id'];

    protected $casts = [
        'due_at' => 'date',
    ];

    public function program()
    {
        return $this->belongsTo(CompanyWellbeingProgram::class, 'program_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
