<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\TenantConsultantPoolController;

// ONB.5: BK mengelola pool konsultan sekolah sendiri.
class ConsultantPoolController extends TenantConsultantPoolController
{
    protected const VERTICAL = 'sekolah';
}