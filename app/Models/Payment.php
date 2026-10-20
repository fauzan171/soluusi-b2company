<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * 
     */
    public function adminFee($total) { 
        
        if($this->type == "PERCENT"){
            $persentValue = $total*$this->biaya_admin;
            $biaya = $persentValue+$this->biaya_tambahan;
        }else{
            $biaya = $this->biaya_admin+$this->biaya_tambahan;
        }

        return $biaya;
    }
}
