<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Helpers\FarmScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accessibleFarmIds = FarmScope::accessibleFarmIds($user);

        if (empty($accessibleFarmIds)) {
            abort(403, 'No farm access.');
        }

        // Owner can choose farm, manager is locked to their farm
        $selectedFarmId = $user->hasRole(['admin', 'owner'])
            ? (int) ($request->query('farm_id') ?: $accessibleFarmIds[0])
            : (int) (FarmScope::managerFarmId($user) ?? 0);

        if (! in_array($selectedFarmId, $accessibleFarmIds, true)) {
            abort(403, 'Invalid farm scope.');
        }

        $farms = DB::table('farms')
            ->whereIn('id', $accessibleFarmIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $staff = DB::table('farm_staff as fs')
            ->join('users as u', 'u.id', '=', 'fs.worker_id')
            ->where('fs.farm_id', $selectedFarmId)
            ->whereNull('u.deleted_at')
            ->select('u.id', 'u.name', 'u.email', 'u.phone', 'u.is_active', 'fs.link_date')
            ->orderBy('u.name')
            ->get();

        return view('admin.staff.staff', compact('staff', 'farms', 'selectedFarmId'));
    }

    // For edit modal AJAX
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $accessibleFarmIds = FarmScope::accessibleFarmIds($user);

        $row = DB::table('farm_staff as fs')
            ->join('users as u', 'u.id', '=', 'fs.worker_id')
            ->where('u.id', $id)
            ->whereIn('fs.farm_id', $accessibleFarmIds)
            ->whereNull('u.deleted_at')
            ->select('u.id', 'u.name', 'u.email', 'u.phone', 'u.is_active', 'fs.farm_id')
            ->first();

        if (! $row) {
            abort(404);
        }

        return response()->json($row);
    }

    public function store(Request $request)
    {
        $auth = $request->user();
        $accessibleFarmIds = FarmScope::accessibleFarmIds($auth);

        $farmId = (int) $request->input('farm_id');
        if (! in_array($farmId, $accessibleFarmIds, true)) {
            abort(403, 'Invalid farm scope.');
        }

        $validated = $request->validate([
            'farm_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required', 'email', 'max:191',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'phone' => [
                'required', 'string', 'max:191',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $farmId) {
            $staff = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            $staff->assignRole('worker');

            // Link to farm_staff (farm_id, worker_id, link_date)
            DB::table('farm_staff')->insert([
                'farm_id' => $farmId,
                'worker_id' => $staff->id,
                'link_date' => now(),
            ]);
        });

        return back()->with('success', 'Staff added successfully.');
    }

    public function update(Request $request, int $id)
    {
        $auth = $request->user();
        $accessibleFarmIds = FarmScope::accessibleFarmIds($auth);

        // Ensure staff belongs to accessible farms
        $staffFarmId = DB::table('farm_staff')
            ->where('worker_id', $id)
            ->whereIn('farm_id', $accessibleFarmIds)
            ->value('farm_id');

        if (! $staffFarmId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at'),
            ],
            'phone' => [
                'required', 'string', 'max:191',
                Rule::unique('users', 'phone')->ignore($id)->whereNull('deleted_at'),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($id, $validated) {
            $u = User::query()->where('id', $id)->whereNull('deleted_at')->firstOrFail();

            $u->name = $validated['name'];
            $u->email = $validated['email'];
            $u->phone = $validated['phone'];
            $u->is_active = (bool) ($validated['is_active'] ?? true);

            if (! empty($validated['password'])) {
                $u->password = Hash::make($validated['password']);
            }

            $u->save();

            // Ensure role is staff (optional safeguard)
            if (! $u->hasRole('worker')) {
                $u->syncRoles(['worker']);
            }
        });

        return back()->with('success', 'Staff updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $auth = $request->user();
        $accessibleFarmIds = FarmScope::accessibleFarmIds($auth);

        $exists = DB::table('farm_staff')
            ->where('worker_id', $id)
            ->whereIn('farm_id', $accessibleFarmIds)
            ->exists();

        if (! $exists) {
            abort(403);
        }

        // Soft delete staff user (users has deleted_at)
        User::query()->where('id', $id)->firstOrFail()->delete();

        return back()->with('success', 'Staff deleted successfully.');
    }
}
