<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing');
    }

    public function storeLead(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'org_name' => 'required|string|max:255',
            'vertical' => 'required|in:klinik,sekolah,perusahaan',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'message' => 'nullable|string|max:1000',
        ]);

        Lead::create($data);

        return back()->with('lead_success', 'Terima kasih! Tim kami akan menghubungi kamu dalam 1×24 jam kerja.');
    }
}
