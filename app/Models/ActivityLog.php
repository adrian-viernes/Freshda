<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['user_id', 'email', 'action', 'description', 'ip'];

    /**
     * Record one audit entry. Safe to call from any account action; the
     * user may be gone already (hence the nullable email column).
     */
    public static function record(string $action, ?User $user, string $description = ''): self
    {
        return static::create([
            'user_id' => $user?->id,
            'email' => $user?->email ?? request()->input('email'),
            'action' => $action,
            'description' => $description,
            'ip' => request()?->ip(),
        ]);
    }
}
