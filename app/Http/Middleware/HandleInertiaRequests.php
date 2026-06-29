<?php

namespace App\Http\Middleware;

use App\References\ListClass;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';
    protected $menu;

    public function __construct(ListClass $menu)
    {
        $this->menu = $menu;
    }


    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'user' => fn() => tap(Auth::user()?->load(['role', 'profile', 'profile.agency']), function ($user) {
                if ($user && $user->profile && $user->profile->avatar) {
                    $user->profile->avatar_url = asset(
                        'storage/avatars/' . $user->id . '/' . $user->profile->avatar
                    );
                }
            }),
            'notif' => fn() => auth('web')
                ->user()
                ?->notifications()
                ->latest()
                ->limit(50)
                ->get()
                ->map(function ($notification) {
                    $notification->diff_time = $notification->created_at
                        ? Carbon::parse($notification->created_at)->diffForHumans()
                        : null;
                    return $notification;
                }),
            'menu' => fn() => $this->menu?->getMenu('sidebar'),
            'permissions' => fn() => app(SystemPermissions::class)->permissionsFor(Auth::user()),
        ]);
    }
}
