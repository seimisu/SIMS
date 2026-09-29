<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LocationRegionRequest;
use App\Models\LocationRegions;
use App\References\LocationClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LocationRegionController extends Controller
{
    public function index(LocationClass $ref, Request $request)
    {
        return Inertia::render('Web/locationRegionPage', [
            'region' => $ref->get_regions(true, $request->input('search'))
        ]);
    }

    public function store(LocationRegionRequest $request)
    {
        $data = $request->validated();

        $region = LocationRegions::firstOrCreate([
            'code' => $data['code'],
            'is_delete' => false,
        ], [
            'name' => $data['name'],
            'region' => $data['region'],
            'island' => $data['island']['name'],
            'created_by'    => Auth::user()->profile->fullname
        ]);

        return redirect()->back()->with('flash', [
            'status' => $region->wasRecentlyCreated ? 'success' : 'info',
            'title'  => $region->wasRecentlyCreated ? 'Region Created' : 'Region Already Exists',
            'message' => $region->wasRecentlyCreated ? 'Region successfully created.' : 'This region already exists, so no duplicate was created.',
        ]);;
    }
    function update(LocationRegionRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = LocationRegions::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'name' => $data['name'],
                'region' => $data['region'],
                'code' => $data['code'],
                'island' => $data['island']['name'],
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
            'title'  => 'Region Updated',
            'message' => 'Region successfully updated.',
        ]);
    }

    function destroy(int $id)
    {
        $find = LocationRegions::findOrFail($id);
        $find->update([
            'is_delete' => true,

        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Region Deleted',
            'message' => 'Region successfully deleted.',
        ]);
    }
}
