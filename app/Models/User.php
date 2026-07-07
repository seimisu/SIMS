<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'email',
        'school_id',
        'is_active',
        'is_verified',
        'is_delete',
        'password',
        'remember',
        'activation_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'activation_token',
        'role',
    ];

    protected $appends = ['formatted_date', 'role_array', 'school_array'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function logs()
    {
        return $this->hasMany(ActivityLogs::class, 'user_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(ListRole::class, 'role_id');
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->created_at)->format('M d, Y | h:i a');
    }

    public function school()
    {
        return $this->belongsTo(SchoolCampuses::class, 'school_id', 'id');
    }

    public function getRoleArrayAttribute()
    {
        return $this->role ? [
            'id' => $this->role->id,
            'name' => $this->role->name,
        ] : null;
    }

    public function getSchoolArrayAttribute()
    {
        return $this->school_id ? [
            'id' => $this->school->id,
            'name' => $this->school->generated_name,
        ] : null;
    }
}
