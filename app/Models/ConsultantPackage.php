<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultantPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'mode',
        'price',
        'duration',
        'quota',
        'consultant_id'
    ];

    protected $casts = [
        'price' => 'double',
        'duration' => 'integer',
        'quota' => 'integer',
        'results' => 'array'
    ];

    public const MODE_ONLINE = 'online';
    public const MODE_OFFLINE = 'offline';

    /**
     * get consultant with this ConsultantPackage
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * get output with this ConsultantPackage
     */
    public function outputs()
    {
        return $this->hasMany(Output::class);
    }

    /**
     * get consultation session with this ConsultantPackage
     */
    public function consultationSessions()
    {
        return $this->hasMany(ConsultationSession::class);
    }
}
