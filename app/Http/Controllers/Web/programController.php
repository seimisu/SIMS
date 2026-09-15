<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProgramRequest;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class programController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Web/programPage', [
            'programs' => ListPrograms::with(['program', 'type'])->when($request->input('search'), function ($q) use ($request) {
                $search = strtolower(request('search'));
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%']);
            })->where('is_delete', false)->orderBy('id', 'desc')->paginate(10),
            'scholarshipOptions' => ListReferences::select('id', 'name')
                ->where([
                    ['classification', '=', 'Scholarship'],
                    ['is_delete', '=', false],
                ])->get()
                ->makeHidden(['classification_array', 'type_array', 'color_array', 'reference_array']),
            'typeOptions' => ListReferences::select('id', 'name')
                ->where([
                    ['type', '=', 'Type'],
                    ['is_delete', '=', false],
                ])->get()
                ->makeHidden(['classification_array', 'type_array', 'color_array', 'reference_array']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProgramRequest $request)
    {
        $data = $request->validated();

        $program = ListPrograms::firstOrCreate([
            'name' => $data['name'],
            'program_id' => $data['scholarship']['id'] ?? null,
            'type_id' => $data['type']['id'] ?? null,
            'is_sub' => $data['isSub'] ?? false,
            'is_delete' => false,
        ], [
            'description' => $data['description'] ?? null,
            'created_by' =>  Auth::user()->profile->fullname
        ]);

        return redirect()->back()->with('flash', [
            'status' => $program->wasRecentlyCreated ? 'success' : 'info',
            'title'  => $program->wasRecentlyCreated ? 'Program Created' : 'Program Already Exists',
            'message' => $program->wasRecentlyCreated ? 'Program successfully created.' : 'This program already exists, so no duplicate was created.',
        ]);;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    function update(ProgramRequest $request, string $id, string $type)
    {
        $data = $request->validated();
        $find = ListPrograms::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'program_id' => $data['scholarship']['id'] ?? null,
                'type_id' => $data['type']['id'] ?? null,
                'is_sub' => $data['isSub'] ?? false,
                'updated_by'    => Auth::user()->profile->fullname,
                'updated_at'    => now()
            ]);
        } else {
            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Program Updated',
            'message' => 'Program successfully updated.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $find = ListPrograms::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Program Deleted',
            'message' => 'Program successfully deleted.',
        ]);
    }
}
