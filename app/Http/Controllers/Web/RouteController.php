<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RouteRequest;
use App\Models\ListRoutes;
use App\References\ListClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RouteController extends Controller
{
    function index(ListClass $reference, Request $request)
    {

        return Inertia::render('Web/routePage', [
            'routes' => $reference->getMenu('', $request->input('search')),
            'routeOption' => $reference->getMenu('option'),
            'roleOption' => $reference->getRoles(false)
        ]);
    }


    public function store(RouteRequest $request)
    {
        $data = $request->validated();



        $route = ListRoutes::firstOrCreate([
            'slug'          => $data['slug'],
            'is_delete'     => false,
        ], [
            'route'         => $data['route'],
            'label'         => $data['label'],
            'icon'          => $data['icon'],
            'component'     => $data['component'],
            'is_submenu'    => $data['isSubmenu'],
            'main_id'       => $data['submenu']['id'] ?? null,
            'roles'         =>  $data['role'],
            'created_by'    => Auth::user()->profile->fullname
        ]);

        return redirect()->back()->with('flash', [
            'status' => $route->wasRecentlyCreated ? 'success' : 'info',
            'title'  => $route->wasRecentlyCreated ? 'Route Created' : 'Route Already Exists',
            'message' => $route->wasRecentlyCreated ? 'Menu route successfully created.' : 'This route already exists, so no duplicate was created.',
        ]);;
    }
    function update(RouteRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = ListRoutes::findOrFail($id);

        if ($type == 'form') {
            $find->update([
                'route'         => $data['route'],
                'label'         => $data['label'],
                'slug'          => $data['slug'],
                'icon'          => $data['icon'],
                'component'     => $data['component'],
                'is_submenu'    => $data['isSubmenu'],
                'main_id'       => $data['submenu']['id'] ?? null,
                'roles'         =>  $data['role'],
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
            'title'  => 'Route Updated',
            'message' => 'Route successfully updated.',
        ]);
    }

    function destroy(int $id)
    {
        $find = ListRoutes::findOrFail($id);
        $find->update([
            'is_delete' => true,

        ]);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'Route Deleted',
            'message' => 'Route successfully deleted.',
        ]);
    }
}
