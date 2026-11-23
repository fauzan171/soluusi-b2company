<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentInstrument;

/**
 * wv4: admin "Asesmen" — read-only daftar instrumen asesmen.
 * (CRUD instrumen milik track lain; ini entry point nav internal.)
 */
class AssessmentInstrumentsController extends Controller
{
    public function index()
    {
        return view('admin.assessment-instruments', [
            'instruments' => AssessmentInstrument::orderBy('code')->get(),
        ]);
    }
}
