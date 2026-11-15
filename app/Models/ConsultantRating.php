<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'user_id',
        'rating',
    ];

    /**
     * Get the consultant of the rating.
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * Get the user of the rating.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
