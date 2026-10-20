<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantCategory extends Model
{
    use HasFactory;

    protected $fillable = ['consultant_id', 'category_id'];

    protected $primaryKey = ['consultant_id', 'category_id'];
    public $incrementing = false;
    protected $keyType = 'array';

    /**
     * Get consultant
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * Get category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
