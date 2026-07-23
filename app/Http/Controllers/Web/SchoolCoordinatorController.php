<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SchoolCampuses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class SchoolCoordinatorController extends Controller
{
    public function index(Request $request)
    {
        $campus_id = Auth::user()->school_id;
        $campus = SchoolCampuses::find($campus_id);

        return Inertia::render('Web/schoolCoordinatorPage', [
            'campus' => [
                'name' => $campus->generated_name,
                'address' => $campus->address?->full_address['name'],
            ],
            'programs' => $campus
                ->courses()
                ->where('is_delete', false)
                ->paginate(2)->through(function ($course) {
                    return [
                        'id' => Hashids::encode($course->id),
                        'course' => $course->course->name,
                        'scholar' => $course->scholarCampus,
                    ];
                }),
        ]);
    }
}
