<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * get user with this Role
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
