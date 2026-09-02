<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ListAgencies;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserProfileController extends Controller
{

    public function index()
    {

        $currentSessionId = request()->session()->getId();

        return Inertia::render(
            'Web/userProfilePage',
            [
                'agencyOption' => ListAgencies::select('id', 'name')->where('is_active', true)->get(),
                'logs' => DB::table('sessions')
                    ->where('user_id', Auth::id())
                    ->orderByDesc('last_activity')
                    ->limit(10)
                    ->get()
                    ->map(function ($session) use ($currentSessionId) {
                        return [
                            'id' => $session->id,
                            'ip_address' => $session->ip_address,
                            'user_agent' => $session->user_agent,
                            'device' => $this->getDeviceType($session->user_agent),


                            'last_activity' => Carbon::createFromTimestamp($session->last_activity)
                                ->timezone('Asia/Manila')
                                ->diffInDays(Carbon::now('Asia/Manila')) < 5
                                ? Carbon::createFromTimestamp($session->last_activity)
                                ->timezone('Asia/Manila')
                                ->diffForHumans()
                                : Carbon::createFromTimestamp($session->last_activity)
                                ->timezone('Asia/Manila')
                                ->format('M d, Y h:i A'),

                            'status' => $session->id === $currentSessionId
                                ? 'Current'
                                : 'Completed',
                        ];
                    }),
            ]
        );
    }

    public function update(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);

        $validated = $request->validate([
            'firstName' => [
                'required',
                'string',
                'max:100',
            ],

            'lastName' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'position' => [
                'nullable',
                'string',
                'max:255',
            ],

            'office_id' => [
                'nullable',
                'integer',
                'exists:list_agencies,id',
            ],
        ]);

        $user->update([
            'email' => $validated['email'],
        ]);

        $user->profile()->update([
            'fname' => $validated['firstName'],
            'lname' => $validated['lastName'],
            'contact_no' => $validated['phone'],
            'designation' => $validated['position'],
            'agency_id' => $validated['office_id'],
        ]);

        return back()->with([
            'status' => 'success',
            'title' => 'Profile Updated',
            'message' => 'Your profile has been successfully updated.',
        ]);
    }


    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profilePhoto' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);
        $user = Auth::user();
        $profile = $user->profile;
        if ($profile?->avatar) {
            Storage::disk('public')->delete(
                "avatars/{$user->id}/{$profile->avatar}"
            );
        }

        $file = $request->file('profilePhoto');

        // Get extension
        $extension = $file->getClientOriginalExtension();

        // Generate filename
        $filename = 'profile_' . time() . '.' . $extension;

        // Store:
        // storage/app/public/avatars/{user_id}/{filename}
        Storage::disk('public')->putFileAs(
            "avatars/{$user->id}",
            $file,
            $filename
        );

        // Save only filename to profile table
        $profile->update([
            'avatar' => $filename,
        ]);

          return back()->with([
            'status' => 'success',
            'title' => 'Profile picture Updated',
            'message' => 'Your picture has been successfully updated.',
        ]);
    }

    private function getDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        return match (true) {
            preg_match('/iPhone/i', $userAgent) => 'iPhone',
            preg_match('/iPad/i', $userAgent) => 'iPad',
            preg_match('/Android/i', $userAgent) => 'Android',
            preg_match('/Windows/i', $userAgent) => 'Windows',
            preg_match('/Macintosh|Mac OS X/i', $userAgent) => 'Mac',
            preg_match('/Linux/i', $userAgent) => 'Linux',
            default => 'Unknown',
        };
    }
}
