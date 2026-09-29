<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReferenceRequest;
use App\Models\ListReferences;
use App\References\ListClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReferenceController extends Controller
{
    public function index(ListClass $ref, Request $request)
    {
        return Inertia::render('Web/referencePage', [
            'references' => $ref->getRefs('main', $request->input('search')),
            'classOption' => $ref->getRefs('distinct', null, 'classification'),
            'typeOption' => $ref->getRefs('distinct', null, 'type'),
        ]);
    }

    public function store(ReferenceRequest $request)
    {
        $data = $request->validated();

        $reference = ListReferences::firstOrCreate([
            'name' => $data['name'],
            'classification' => $data['class']['id'] ?? null,
            'type' => $data['typeReference']['id'] ?? null,
            'others' => $data['others'],
            'is_delete' => false,
        ], [
            'color' => $data['color']['id'] ?? null,
            'created_by'    => Auth::user()->profile->fullname
        ]);

        return redirect()->back()->with('flash', [
            'status' => $reference->wasRecentlyCreated ? 'success' : 'info',
            'title'  => $reference->wasRecentlyCreated ? 'Reference Created' : 'Reference Already Exists',
            'message' => $reference->wasRecentlyCreated ? 'Reference successfully created.' : 'This reference already exists, so no duplicate was created.',
        ]);;
    }
    function update(ReferenceRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = ListReferences::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'name' => $data['name'],
                'classification' => $data['class']['id'] ?? null,
                'type' => $data['typeReference']['id'] ?? null,
                'color' => $data['color']['id'] ?? null,
                'others' => $data['others'],
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
            'title'  => 'Reference Updated',
            'message' => 'Reference successfully updated.',
        ]);
    }

    function destroy(int $id)
    {
        $find = ListReferences::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Reference Deleted',
            'message' => 'Reference successfully deleted.',
        ]);
    }
}
