<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\TenantConsultantPoolController;

// ONB.5: HR mengelola pool konsultan perusahaan sendiri.
class ConsultantPoolController extends TenantConsultantPoolController
{
    protected const VERTICAL = 'perusahaan';
}