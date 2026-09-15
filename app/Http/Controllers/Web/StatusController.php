<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StatusRequest;
use App\Models\ListStatuses;
use App\References\ListClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StatusController extends Controller
{
    public function index(ListClass $ref, Request $request)
    {
        return Inertia::render('Web/scholarStatusPage', [
            'status'         => $ref->getStatuses(true, $request->input('search')),
            'colorOption'    => $ref->getColors(false)
        ]);
    }

    public function store(StatusRequest $request)
    {
        $data = $request->validated();

        $status = ListStatuses::firstOrCreate([
            'name' => $data['name'],
            'type' => $data['type'],
            'is_delete' => false,
        ], [
            'icon' => $data['icon'],
            'color_id' => $data['color']['id'],
            'created_by'    => Auth::user()->profile->fullname
        ]);

        return redirect()->back()->with('flash', [
            'status' => $status->wasRecentlyCreated ? 'success' : 'info',
            'title'  => $status->wasRecentlyCreated ? 'Status Created' : 'Status Already Exists',
            'message' => $status->wasRecentlyCreated ? 'Status successfully created.' : 'This status already exists, so no duplicate was created.',
        ]);;
    }
    function update(StatusRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = ListStatuses::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'name' => $data['name'],
                'icon' => $data['icon'],
                'type' => $data['type'],
                'color_id' => $data['color']['id'],
                'updated_by'    => Auth::user()->profile->fullname,
            ]);
        } else {
            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Status Updated',
            'message' => 'Status successfully updated.',
        ]);
    }

    function destroy(
        int $id
    ) {
        $find = ListStatuses::findOrFail($id);
        $find->update([
            'is_delete' => true,
        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Status Deleted',
            'message' => 'Status successfully deleted.',
        ]);
    }
}
