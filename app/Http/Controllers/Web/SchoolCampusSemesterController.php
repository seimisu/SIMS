<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SchoolCampusSemesterRequest;
use App\Models\SchoolCampusSemesters;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class SchoolCampusSemesterController extends Controller
{
    public function store(SchoolCampusSemesterRequest $request)
    {
        $data = $request->validated();




        foreach ($data['semester'] as $key => $semester) {




            SchoolCampusSemesters::updateOrCreate([
                'campus_id' => $data['campusId'],
                'semester_id' => $semester['semesterId'],
            ], [
                'start_date' => Carbon::parse($data['semester'][$key]['startDateFormatted'])->startOfMonth()->format('Y-m-d'),
                'end_date' => Carbon::parse($data['semester'][$key]['endDateFormatted'])->startOfMonth()->format('Y-m-d'),
                'submission_date' => Carbon::parse($data['semester'][$key]['submissionDateFormatted'])->format('Y-m-d'),
                'is_active' => true,
                'created_by' => Auth::user()->profile->fullname,
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Semesters Added',
            'message' => 'Semesters successfully added to the campus.',
        ]);
    }

    public function update(SchoolCampusSemesterRequest $request, $id, $type)
    {


        $data = $request->validated();



        if ($data['semester'] == null) {
            return redirect()->back()->with('flash', [
                'status' => 'info',
                'title'  => 'No Semesters Added',
                'message' => 'No semesters were added to update.',
            ]);
        }

        foreach ($data['semester'] as $semester) {

            $find = SchoolCampusSemesters::find($semester['id']);
            if ($find) {
                $fields = [
                    'start_date' => Carbon::parse($semester['startDateFormatted'])->format('Y-m-d'),
                    'end_date' => Carbon::parse($semester['endDateFormatted'])->format('Y-m-d'),
                    'submission_date' => Carbon::parse($semester['submissionDateFormatted'])->format('Y-m-d'),
                    'updated_by' => Auth::user()->profile->fullname,
                ];
                $find->fill($fields);

                if ($find->isDirty()) {
                    $find->save();
                }
            }
        }


        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Semesters Updated',
            'message' => 'Semesters successfully updated for the campus.',
        ]);
    }
}
