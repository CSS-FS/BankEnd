<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = QueryBuilder::for(User::class)
            ->with('media')
            ->withCount('farms')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
            ])
            ->allowedIncludes('farms')
            ->allowedSorts(['id', 'name'])
            ->get();

        return UserResource::collection($users);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return UserResource::make(
            User::with([
                'farms.sheds.flocks',
                'farms' => fn ($query) => $query->withCount('sheds'),
            ])
                ->withCount('farms')
                ->findOrFail($user->id)
        );
    }

    public function update(Request $request, User $user)
    {
        // Mobile-friendly: accepts JSON or multipart/form-data.
        // Flutter (image_picker) usually sends multipart with field name "avatar".
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'avatar' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Build update payload only from provided keys
        $toUpdate = [];
        foreach (['name', 'email', 'phone'] as $k) {
            if (array_key_exists($k, $data)) {
                $toUpdate[$k] = $data[$k];
            }
        }
        if (! empty($data['password'])) {
            $toUpdate['password'] = Hash::make($data['password']);
        }

        $hasNewAvatarFile = $request->hasFile('avatar');

        if (empty($toUpdate) && ! $hasNewAvatarFile) {
            return response()->json([
                'message' => 'No data provided for update.',
                'user' => UserResource::make($user->load('media')),
            ], 400);
        }

        // Save user fields first
        if (! empty($toUpdate)) {
            $user->fill($toUpdate)->save();
        }

        // Avatar removal
        if ($existing = $user->media()->first()) {
            // HasMedia provides deleteMedia(id)
            $user->deleteMedia($existing->id);
        }

        // Avatar upload (file wins over base64 if both provided)
        if ($hasNewAvatarFile) {
            // UploadedFile from image_picker multipart
            $uploaded = $request->file('avatar');
            // Remove old
            if ($existing = $user->media()->first()) {
                $user->deleteMedia($existing->id);
            }
            // HasMedia provides addMedia(UploadedFile $file, string $directory = 'media')
            $user->addMedia($uploaded);
        }

        $user->refresh();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => UserResource::make($user->load('media')),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ], 200);
    }
}
