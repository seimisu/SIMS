<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CampusGradeRequest;
use App\Models\SchoolCampusGrades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CampusGradeController extends Controller
{
    public function store(CampusGradeRequest $request)
    {
        $data = $request->validated();
        $this->validateGradeRule($data);

        SchoolCampusGrades::create([
            'campus_id' => $data['campusId'],
            'grade' => $data['grade'],
            'lower' => $data['lower'],
            'upper' => $data['upper'],
            'is_failed' => $data['fail'],
            'is_drop' => $data['drop'],
            'is_incomplete' => $data['incomplete'],
            'is_withdrawn' => $data['withdrawn'] ?? false,
            'created_by' => Auth::user()->profile->fullname
        ]);



        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Campus Grade Created',
            'message' => 'Campus grade successfully created.',
        ]);
    }


    public function update(CampusGradeRequest $request, string $id, string $type)
    {
        $data = $request->validated();
        $find = SchoolCampusGrades::findOrFail($id);

        if ($type == 'form') {
            $this->validateGradeRule($data, $find->id);

            $find->update([
                'grade' => $data['grade'],
                'lower' => $data['lower'],
                'upper' => $data['upper'],
                'is_failed' => $data['fail'],
                'is_drop' => $data['drop'],
                'is_incomplete' => $data['incomplete'],
                'is_withdrawn' => $data['withdrawn'] ?? false,
                'created_by' => Auth::user()->profile->fullname
            ]);
        } else {
            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Campus Course Updated',
            'message' => 'Campus course successfully updated.',
        ]);
    }

    function destroy(int $id)
    {

        $find = SchoolCampusGrades::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Campus Course Deleted',
            'message' => 'Campus course successfully deleted.',
        ]);
    }

    private function validateGradeRule(array $data, ?int $ignoreId = null): void
    {
        $isDrop = (bool) ($data['drop'] ?? false);
        $isIncomplete = (bool) ($data['incomplete'] ?? false);
        $isWithdrawn = (bool) ($data['withdrawn'] ?? false);
        $isFailed = (bool) ($data['fail'] ?? false);

        if (collect([$isDrop, $isIncomplete, $isWithdrawn, $isFailed])->filter()->count() > 1) {
            throw ValidationException::withMessages([
                'grade' => 'Only one grade classification flag can be enabled.',
            ]);
        }

        if (! $isDrop && ! $isIncomplete && ! $isWithdrawn && (($data['lower'] ?? null) === null || ($data['upper'] ?? null) === null)) {
            throw ValidationException::withMessages([
                'lower' => 'Lower and upper limits are required for grade range rules.',
            ]);
        }

        if ($isDrop || $isIncomplete || $isWithdrawn) {
            return;
        }

        $lower = (float) $data['lower'];
        $upper = (float) $data['upper'];
        $minimum = min($lower, $upper);
        $maximum = max($lower, $upper);

        $overlap = SchoolCampusGrades::where('campus_id', $data['campusId'])
            ->where('is_delete', false)
            ->where('is_drop', false)
            ->where('is_incomplete', false)
            ->where('is_withdrawn', false)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get()
            ->contains(function ($rule) use ($minimum, $maximum) {
                if (! is_numeric($rule->lower) || ! is_numeric($rule->upper)) {
                    return false;
                }

                $ruleMinimum = min((float) $rule->lower, (float) $rule->upper);
                $ruleMaximum = max((float) $rule->lower, (float) $rule->upper);

                return $minimum <= $ruleMaximum && $maximum >= $ruleMinimum;
            });

        if ($overlap) {
            throw ValidationException::withMessages([
                'lower' => 'This grade range overlaps an existing active campus grading range.',
            ]);
        }
    }
}
