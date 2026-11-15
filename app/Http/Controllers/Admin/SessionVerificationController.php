<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Klinik\SessionController;
use App\Models\AdminAction;
use App\Models\ConsultationSession;

class SessionVerificationController extends Controller
{
    /** Ops manual verify (dual-confirm fallback, Spec §9.1 step 4). */
    public function store(ConsultationSession $session)
    {
        abort_if($session->completion_verified_at, 422, 'Sudah terverifikasi.');
        $session->customer_confirmed_at = $session->customer_confirmed_at ?? now();
        $session->needs_ops_review = false;
        $session->save();
        SessionController::customerConfirm($session);

        AdminAction::record(auth()->id(), 'session.verify', $session);

        return back()->with('success', 'Sesi diverifikasi manual oleh ops.');
    }
}
