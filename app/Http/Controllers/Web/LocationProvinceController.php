<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LocationProvinceRequest;
use App\Models\LocationProvinces;
use App\References\LocationClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LocationProvinceController extends Controller
{
    public function index(LocationClass $ref, Request $request)
    {
        return Inertia::render('Web/locationProvincePage', [
            'provinces'         => $ref->get_provinces(true, $request->input('search')),
            'provinceOption'    => $ref->get_regions(false)
        ]);
    }

    public function store(LocationProvinceRequest $request)
    {
        $data = $request->validated();

        $province = LocationProvinces::firstOrCreate([
            'code' => $data['code'],
            'is_delete' => false,
        ], [
            'name' => $data['name'],
            'old_name' => $data['oldName'],
            'region_code' => $data['region']['id'],
            'created_by'    => Auth::user()->profile->fullname
        ]);

        return redirect()->back()->with('flash', [
            'status' => $province->wasRecentlyCreated ? 'success' : 'info',
            'title'  => $province->wasRecentlyCreated ? 'Province Created' : 'Province Already Exists',
            'message' => $province->wasRecentlyCreated ? 'Province successfully created.' : 'This province already exists, so no duplicate was created.',
        ]);;
    }
    function update(LocationProvinceRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = LocationProvinces::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'name' => $data['name'],
                'old_name' => $data['oldName'],
                'code' => $data['code'],
                'region_code' => $data['region']['id'],
                'updated_by'    => Auth::user()->profile->fullname,
                'updated_at'    =>  now()
            ]);
        } else {
            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Province Updated',
            'message' => 'Province successfully updated.',
        ]);
    }

    function destroy(int $id)
    {
        $find = LocationProvinces::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Province Deleted',
            'message' => 'Province successfully deleted.',
        ]);
    }
}
