<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UserRequest;
use App\Mail\UserCreatedMail;
use App\Models\SchoolCampuses;
use App\Models\User;
use App\References\ListClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request, ListClass $reference)
    {
        $user = User::where('is_delete', false)
            ->when($request->input('search'), function ($query) {
                $search = strtolower(request('search'));

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('profile', function ($q2) use ($search) {
                            $q2->whereRaw('LOWER(fname) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(lname) LIKE ?', ["%{$search}%"]);
                        });
                });
            })
            ->with(['role', 'profile', 'school'])
            ->orderBy('id')
            ->paginate(10);

        // Add avatar URL to each user
        $user->getCollection()->transform(function ($u) {
            $u->avatar_url = $u->profile && $u->profile->avatar
                ? asset("storage/avatars/{$u->id}/{$u->profile->avatar}")
                : null;

            return $u;
        });

        return Inertia::render('Web/userPage', [
            'users' => $user,
            'roleOption' => $reference->getRoles(false),
            'schoolOption' => SchoolCampuses::where([
                'is_delete' => false,
                'is_active' => true,
                'agency_id' => request()->input('agency'),
            ])->get()->map(function ($campus) {
                return [
                    'id' => $campus->id,
                    'name' => $campus->generated_name,
                ];
            }),
        ]);
    }

    public function store(UserRequest $request)
    {

        $data = $request->validated();
        $activation = Str::random(64);

        $user = User::create([
            'email' => $data['email'],
            'role_id' => $data['role']['id'],
            'activation_token' => $activation,

        ]);

        $user->profile()->create([
            'fname' => Str::lower($data['fname']),
            'lname' => Str::lower($data['lname']),
        ]);

        Mail::to($data['email'])->send(new UserCreatedMail($user, $activation));

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'User Created',
            'message' => 'User successfully created.',
        ]);
    }

    public function resend(string $id)
    {
        $user = User::findOrFail($id);
        $activation = Str::random(64);

        if ($user->is_verified) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Cannot Resend Email',
                'message' => 'User is already verified.',
            ]);
        }

        $user->update([
            'activation_token' => $activation,
        ]);
        Mail::to($user->email)->send(new UserCreatedMail($user, $activation));

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Email Resent',
            'message' => 'User activation email successfully resent.',
        ]);
    }

    public function update(UserRequest $request, string $id, string $type)
    {

        $data = $request->validated();
        $find = User::findOrFail($id);

        switch ($type) {
            case 'status':
                $find->update([
                    'is_active' => $data['isActive'],
                ]);
                break;

            default:
                $find->update([
                    'email' => $data['email'],
                    'role_id' => $data['role']['id'],
                ]);
                $find->profile()->update([
                    'fname' => $data['fname'],
                    'lname' => $data['lname'],
                ]);
                break;
        }

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'User Updated',
            'message' => 'User successfully updated.',
        ]);
    }

    public function destroy(UserRequest $request, int $id)
    {

        $find = User::findOrFail($id);

        $find->profile()->delete();
        $find->delete();

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'User Deleted',
            'message' => 'User successfully deleted.',
        ]);
    }
}
