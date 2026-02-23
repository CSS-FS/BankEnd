<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with([
            'media',
            'farms',
            'managedFarms',
        ])->get();
        $roles = Role::all();
        $countries = Country::orderBy('country')->get();

        return view(
            'admin.users.index',
            compact('users', 'roles', 'countries')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No implementation is required
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/'],
            'phone' => [
                'required', 'string', 'unique:users',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('phone_country') === 'PK') {
                        if (! preg_match('/^\d{11}$/', $value)) {
                            $fail('Pakistani phone number must be exactly 11 numeric digits (e.g. 03001234567).');
                        }
                    }
                },
            ],
            'phone_country' => 'required|string|size:2',
            'role' => 'required|string|exists:roles,name',
            'file' => 'nullable|mimes:jpeg,jpg,png|max:2000',
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'password_reset_required' => true,
        ]);

        $user->assignRole($validated['role']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $user->addMedia($file);
        }

        return redirect()
            ->route('clients.index')
            ->with('success', 'User is added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($userId)
    {
        $user = User::with([
            'farms.sheds.flocks',
            'farms' => fn ($query) => $query->withCount('sheds'),
            'settings',
        ])->withCount('farms')
            ->findOrFail($userId);

        if ($user->settings == null) {
            $user->settings = $user->settings()->create([
                'security_level' => 'medium',
                'backup_frequency' => 'daily',
                'language' => 'en',
                'timezone' => 'UTC',
                'notifications_email' => true,
                'notifications_sms' => false,
            ]);
        }

        $provinces = Province::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view(
            'admin.users.show',
            compact('user', 'provinces')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::with(['media', 'settings', 'roles'])
            ->find($id);

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::with('media')->find($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => [
                'required', 'string',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('phone_country') === 'PK') {
                        if (! preg_match('/^\d{11}$/', $value)) {
                            $fail('Pakistani phone number must be exactly 11 numeric digits (e.g. 03001234567).');
                        }
                    }
                },
            ],
            'phone_country' => 'required|string|size:2',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'nullable|boolean',
            'file' => 'nullable|mimes:jpeg,jpg,png|max:2000',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        $user->syncRoles($validated['role']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if ($user->media != null && $user->media->first()) {
                $user->deleteMedia($user->media->first()->id);
            }
            $user->addMedia($file);
        }

        return redirect()
            ->route('clients.index')
            ->with('success', 'User has been updated successfully.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($userId)
    {
        $user = User::with('media')->findOrFail($userId);
        $media = $user->media->first();
        if ($media) {
            $user->deleteMedia($media->id);
        }
        $user->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'User is deleted successfully.');
    }

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_reset_required' => 1,
        ]);

        return back()
            ->with('success', 'Password has been updated successfully.');
    }

    public function activities(Request $request)
    {
        $query = Activity::query()
            ->with('causer') // assumes causer is User
            ->orderByDesc('created_at');

        // Filters
        if ($request->filled('model')) {
            $query->where('subject_type', $request->model);
        }

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(25)->withQueryString();

        // For filters dropdowns
        $models = Activity::select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $userIds = Activity::whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        $users = User::whereIn('id', $userIds)->orderBy('name')->get();

        $filters = $request->only(['model', 'user_id', 'date_from', 'date_to']);

        return view(
            'admin.users.activities',
            compact(
                'activities',
                'models',
                'users',
                'filters'
            )
        );
    }
}
