<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profile) {}

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_number' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            /* Mirrors the file input's accept list: browsers hand over HEIC
               phone photos that PHP's image check then rejects anyway. */
            'profile_picture' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ]);

        $updated = $this->profile->update($user, $validated, $request->boolean('remove_profile_picture'));

        return response()->json([
            'name' => $updated->name,
            'email' => $updated->email,
            'contact_number' => $updated->contact_number,
            'address' => $updated->address,
            'profile_picture_url' => $updated->profilePictureUrl(),
            'profile_picture_path' => $updated->profile_picture_path,
            'initials' => $updated->initials(),
        ]);
    }
}
