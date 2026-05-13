<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActivationRequest;
use App\Models\SchoolCampuses;
use App\Models\User;
use App\Models\UserProfile;
use App\References\ListClass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Response;

class ActivationController extends Controller
{

    public $agencyOption;

    public function __construct(ListClass $ref)
    {
        $this->agencyOption = $ref->getAgencies(false);
    }

    public function show(string $token)
    {
        try {
            $user = User::select('email', 'role_id', 'id')
                ->with(['profile:user_id,fname,lname'])
                ->where('activation_token', $token)
                ->where('is_verified', false)
                ->firstOrFail();

            return Inertia::render('Auth/activationPage', [
                'token' => $token,
                'user' => $user,
                'agencyOption' => $this->agencyOption,
                'schoolOption' => SchoolCampuses::where([
                    'is_delete' => false,
                    'is_active' => true,
                ])->get()->map(function ($campus) {
                    return [
                        'id' => $campus->id,
                        'name' => $campus->generated_name,
                    ];
                })
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('login');
        }
    }

    public function update(ActivationRequest $request, int $id)
    {
        $data = $request->validated();



        $user = User::findOrFail($id);

        $user->update([
            'password'          => bcrypt($data['password']),
            'activation_token'  => null,
            'is_verified'        => true,
            'school_id'   => $data['school'] ? $data['school']['id'] : null,
            'email'       => $data['email'],
        ]);

        $user->profile()->update([
            'fname'       => Str::upper($data['fname']),
            'lname'       => Str::upper($data['lname']),
            'designation' => Str::lower($data['designation']),
            'agency_id'   => $data['agency']['id'],
            'contact_no'  => $data['contact'],
        ]);



        if ($data['photo']) {
            // Get the Base64 string
            $imageData = $data['photo'];

            // Extract the file type
            preg_match("/^data:image\/(.*);base64,/", $imageData, $type);
            $imageType = $type[1]; // png, jpeg, etc.

            // Remove the Base64 prefix
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);

            // Generate a filename
            $filename = 'profile_' . time() . '.' . $imageType;
            Storage::disk('public')->put("avatars/{$user->id}/{$filename}", $imageData);

            $user->profile()->update([
                'avatar' => $filename
            ]);
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title'  => 'User Activate',
            'message' => 'User successfully activated.',
        ]);
    }
}
