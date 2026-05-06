<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FinancialRequest;
use App\Models\Batches;
use App\Models\ListAgencies;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class StipendController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Web/stipendPage', [
            'agencyOption' =>  ListAgencies::where('is_active', true)
                ->where('is_delete', false)
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
            'batches' => Batches::whereNull('deleted_at')
                ->with([
                    'logs' => fn($q) => $q
                        ->select('id', 'batch_id', 'status', 'remarks', 'action_by', 'created_at')
                        ->orderBy('created_at', 'desc')
                ])
                ->paginate(10)
                ->through(fn($q) => [
                    'id'            => Hashids::encode($q->id),
                    'name'          => $q->name,
                    'region'        => $q->region,
                    'term'          => $q->academic_term,
                    'sy'            => $q->school_year,
                    'user'          => $q->logs->first()->action_by,
                    'created_at'    => Carbon::parse($q->logs->first()->created_at)->format('M d, Y | h:i a'),
                    'remarks'       => $q->logs->first()->remarks,
                    'status'        => $q->logs->first()->status
                ]),
            'details' => request('id')
                ? Batches::select('name')
                ->whereId(Hashids::decode(request('id'))[0] ?? 0)
                ->first()
                : null
        ]);
    }

    public function store(FinancialRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $request->checkfile();


            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = Str::lower(Str::trim($value));
                }
            }

            $years = explode('-', $data['academic_year']);
            $result = substr($years[0], -2) . substr($years[1], -2);
            $name =  Auth::user()->profile?->agency?->code . '_' . $data['academic_term'] . 'AY' .   $result . '_Batch' . $data['batch'];

            $parent = Batches::create([
                'region'        => $data['region']['name'],
                'academic_term' => $data['academic_term'],
                'school_year'   => $data['academic_year'],
                'name'          => $name
            ]);

            $parent->logs()->create([
                'action_by' => Auth::user()->profile?->fullname
            ]);


            DB::commit();
            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title'  => 'Batch created',
                'message' => 'Batch successfully created.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title'  => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id, $type)
    {
        // Handle stipend update
    }

    public function destroy($id, $type)
    {
        // Handle stipend deletion
    }
}