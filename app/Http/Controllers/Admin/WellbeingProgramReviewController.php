<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\WellbeingProgramReview;
use Illuminate\Http\Request;

/**
 * wv2 (research2-infra §3): admin menandai review kuartalan Impact selesai
 * dengan ringkasan. Rows dibuat otomatis saat assign (wizard store,
 * same transaction). HR read-only di program-subscription.
 */
class WellbeingProgramReviewController extends Controller
{
    public function complete(Request $request, WellbeingProgramReview $review)
    {
        $data = $request->validate([
            'summary' => ['required', 'string', 'max:2000'],
        ]);

        abort_if($review->status === WellbeingProgramReview::STATUS_COMPLETED, 422,
            'Periode review ini sudah selesai.');

        $review->update([
            'status' => WellbeingProgramReview::STATUS_COMPLETED,
            'summary' => $data['summary'],
            'created_by' => auth()->id(),
        ]);

        AdminAction::record(auth()->id(), 'wellbeing_program_review.completed', $review, [
            'program_id' => $review->program_id,
            'period_no' => $review->period_no,
        ]);

        return back()->with('success', 'Review kuartalan periode '.$review->period_no.' diselesaikan.');
    }
}
