<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LocationBarangayRequest;
use App\Models\LocationBarangays;
use App\References\LocationClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LocationBarangayController extends Controller
{
    public function index(LocationClass $ref, Request $request)
    {
        return Inertia::render('Web/locationBarangayPage', [
            'barangay'         => $ref->get_barangay(true, $request->input('search')),
            'barangayOption'    => $ref->get_cities(false)
        ]);
    }

    public function store(LocationBarangayRequest $request)
    {
        $data = $request->validated();

        $barangay = LocationBarangays::firstOrCreate([
            'code' => $data['code'],
            'is_delete' => false,
        ], [
            'name' => $data['name'],
            'old_name' => $data['oldName'],
            'district' => $data['district'],
            'municipality_code' => $data['municipality']['id'],
            'created_by'    => Auth::user()->profile->fullname
        ]);

        return redirect()->back()->with('flash', [
            'status' => $barangay->wasRecentlyCreated ? 'success' : 'info',
            'title'  => $barangay->wasRecentlyCreated ? 'Barangay Created' : 'Barangay Already Exists',
            'message' => $barangay->wasRecentlyCreated ? 'Barangay successfully created.' : 'This barangay already exists, so no duplicate was created.',
        ]);;
    }
    function update(LocationBarangayRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = LocationBarangays::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'name' => $data['name'],
                'old_name' => $data['oldName'],
                'district' => $data['district'],
                'code' => $data['code'],
                'municipality_code' => $data['municipality']['id'],
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
            'title'  => 'Barangay Updated',
            'message' => 'Barangay successfully updated.',
        ]);
    }

    function destroy(
        int $id
    ) {
        $find = LocationBarangays::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Barangay Deleted',
            'message' => 'Barangay successfully deleted.',
        ]);
    }
}
