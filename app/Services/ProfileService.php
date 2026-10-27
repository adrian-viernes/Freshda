<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Applies an edited profile: the optional text fields, the rules that decide
 * whether a photo is swapped or removed, and the save itself. Validation
 * stays with the request and the response stays with the controller.
 */
class ProfileService
{
    /**
     * @param  array{name: string, email: string, contact_number: ?string, address: ?string, profile_picture?: UploadedFile}  $validated
     */
    public function update(User $user, array $validated, bool $removePicture): User
    {
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->contact_number = $this->optionalText($validated['contact_number'] ?? null);
        $user->address = $this->optionalText($validated['address'] ?? null);

        /* A newly picked photo always wins over a pending removal request. */
        if (isset($validated['profile_picture'])) {
            if ($user->profile_picture_path) {
                Storage::disk('public')->delete($user->profile_picture_path);
            }

            $user->profile_picture_path = $validated['profile_picture']->store('profile-pictures', 'public');
        } elseif ($removePicture && $user->profile_picture_path) {
            Storage::disk('public')->delete($user->profile_picture_path);
            $user->profile_picture_path = null;
        }

        $user->save();

        return $user;
    }

    /**
     * Normalize an optional text field so blank input clears the stored value.
     */
    private function optionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
