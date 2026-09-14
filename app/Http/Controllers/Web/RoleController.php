<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RoleRequest;
use App\Models\ListPermission;
use App\Models\ListRole;
use App\References\ListClass;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


class RoleController extends Controller
{
    function index(ListClass $reference, Request $request)
    {
        return Inertia::render('Web/rolePage', [
            'roles' => $reference->getRoles(true, $request->input('search')),
            'permissionGroups' => ListPermission::where('is_active', true)
                ->orderBy('group_name')
                ->orderBy('label')
                ->get(['id', 'name', 'label', 'group_name', 'description'])
                ->groupBy('group_name'),
        ]);
    }


    function store(RoleRequest $request)
    {

        $data = $request->validated();

        ListRole::create([
            'name'          => $data['name'],
            'slug'          => $data['slug'],
            'description'   => $data['description'],
            'is_lock'       => $data['isLock'],
            'created_by'    => Auth::user()->profile->fullname
        ]);


        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Role Created',
            'message' => 'Role successfully created.',
        ]);
    }

    function update(RoleRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = ListRole::findOrFail($id);

        if ($type == 'form') {

            $find->update([
                'name'          => $data['name'],
                'slug'          => $data['slug'],
                'description'   => $data['description'],
                'is_lock'       => $data['isLock'],
                'updated_by'    => Auth::user()->profile->fullname,
                'updated_at'    => now()
            ]);
        } elseif ($type == 'permissions') {
            if (! app(SystemPermissions::class)->can(Auth::user(), 'roles.assign-permissions')) {
                abort(403, 'Unauthorized');
            }

            $find->permissions()->sync($data['permissions'] ?? []);
        } else {
            $find->update([
                'is_active' => $data['isActive'],
            ]);
        }



        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Role Updated',
            'message' => 'Role successfully updated.',
        ]);
    }

    function destroy(int $id)
    {
        $find = ListRole::findOrFail($id);
        $find->update([
            'is_delete' => true,

        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Role Deleted',
            'message' => 'Role successfully deleted.',
        ]);
    }
}
