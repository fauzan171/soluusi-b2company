<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'url',
        'image_dir',
        'year',
        'expired_at',
        'consultant_id'
    ];

    /**
     * get consultant with this Certificate
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
