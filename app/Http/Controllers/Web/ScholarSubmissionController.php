<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Scholar\Submission\ScholarSubmissionDecisionService;
use App\Services\Scholar\Submission\ScholarSubmissionPageService;
use Illuminate\Http\Request;

class ScholarSubmissionController extends Controller
{
    public function index(Request $request)
    {
        return app(ScholarSubmissionPageService::class)->index($request);
    }

    public function academicHistoryDecision(string $id, string $type, Request $request)
    {
        return app(ScholarSubmissionDecisionService::class)->academicHistoryDecision($id, $type, $request);
    }
}
