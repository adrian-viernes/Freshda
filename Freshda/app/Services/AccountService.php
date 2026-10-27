<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Account lifecycle work behind the auth controllers: creating a signup,
 * rotating a password and writing the audit entry that belongs to each.
 * Session and cookie handling stays in the controller, because that is HTTP.
 */
class AccountService
{
    /**
     * Create a registered account.
     *
     * Sign-up is vendor-only: the form offers no choice and any role value
     * posted by a client is deliberately ignored.
     *
     * @param  array{name: string, email: string, password: string}  $data
     *
     * @throws ValidationException When the email is taken.
     */
    public function register(array $data): User
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'vendor',
            ]);
        } catch (UniqueConstraintViolationException) {
            /* Two concurrent signups raced past the unique rule. */
            throw ValidationException::withMessages([
                'email' => 'An Account With This Email Already Exists.',
            ]);
        }

        ActivityLog::record('account.registered', $user, 'New account registered');

        return $user;
    }

    /**
     * Replace the account password and record the change. The caller
     * regenerates the session afterwards so a stolen cookie stops working.
     */
    public function changePassword(User $user, string $password): void
    {
        $user->password = $password;
        $user->save();

        ActivityLog::record('password.changed', $user, 'Password changed via profile or settings');
    }
}
