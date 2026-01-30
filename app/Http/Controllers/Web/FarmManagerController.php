<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FarmManagerController extends Controller
{
    public function index(Request $request)
    {
        $owner = $request->user();

        // Owner farms list for filter + modal dropdown
        $ownerFarms = DB::table('farms')
            ->where('owner_id', $owner->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $selectedFarmId = $request->query('farm_id');
        $selectedFarmId = $selectedFarmId ? (int) $selectedFarmId : null;

        // Base query: farms + current manager (if any)
        $q = DB::table('farms as f')
            ->leftJoin('farm_managers as fm', 'fm.farm_id', '=', 'f.id')
            ->leftJoin('users as u', 'u.id', '=', 'fm.manager_id')
            ->where('f.owner_id', $owner->id)
            ->select(
                'f.id',
                'f.name',
                'u.id as manager_id',
                'u.name as manager_name',
                'u.email as manager_email'
            )
            ->orderBy('f.name');

        // Server-side farm filter
        if ($selectedFarmId) {
            $q->where('f.id', $selectedFarmId);
        }

        $farms = $q->get();

        // managers dropdown (only active, not deleted)
        $managers = User::role('manager')
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view(
            'admin.staff.managers',
            compact('farms', 'managers', 'ownerFarms', 'selectedFarmId')
        );
    }

    /**
     * Create manager user and link to a farm (one-farm rule).
     * Tables:
     * - users: has unique email/phone and soft delete column deleted_at :contentReference[oaicite:5]{index=5}
     * - farm_managers: farm_id, manager_id, link_date :contentReference[oaicite:6]{index=6}
     */
    public function createManager(Request $request)
    {
        $owner = $request->user();

        $validated = $request->validate([
            'farm_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:191', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'password_reset_required' => ['nullable', 'boolean'],
        ]);

        $farmId = (int) $validated['farm_id'];

        // ensure farm belongs to owner (farms.owner_id)
        $ownsFarm = DB::table('farms')
            ->where('id', $farmId)
            ->where('owner_id', $owner->id)
            ->exists();

        if (! $ownsFarm) {
            abort(403, 'Invalid farm.');
        }

        DB::transaction(function () use ($validated, $farmId) {
            $u = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'password_reset_required' => (bool) ($validated['password_reset_required'] ?? false),
            ]);

            // Spatie role assignment
            $u->assignRole('manager');

            // One manager per farm: remove existing assignment for this farm
            DB::table('farm_managers')->where('farm_id', $farmId)->delete();

            // One farm per manager: remove if any (new user normally has none, but safe)
            DB::table('farm_managers')->where('manager_id', $u->id)->delete();

            DB::table('farm_managers')->insert([
                'farm_id' => $farmId,
                'manager_id' => $u->id,
                'link_date' => now(),
            ]);
        });

        return back()->with('success', 'Manager created and assigned to farm successfully.');
    }

    public function assign(Request $request, int $farmId)
    {
        $owner = $request->user();

        $farm = DB::table('farms')
            ->where('id', $farmId)
            ->where('owner_id', $owner->id)
            ->first();

        if (! $farm) {
            abort(403);
        }

        $validated = $request->validate([
            'manager_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $managerId = (int) $validated['manager_id'];
        $manager = User::query()->where('id', $managerId)->whereNull('deleted_at')->firstOrFail();

        if (! $manager->hasRole('manager')) {
            return back()->withErrors(['manager_id' => 'Selected user is not a manager.']);
        }

        DB::transaction(function () use ($farmId, $managerId) {
            // one-farm per manager
            DB::table('farm_managers')->where('manager_id', $managerId)->delete();
            // one manager per farm
            DB::table('farm_managers')->where('farm_id', $farmId)->delete();

            DB::table('farm_managers')->insert([
                'farm_id' => $farmId,
                'manager_id' => $managerId,
                'link_date' => now(),
            ]);
        });

        return back()->with('success', 'Manager assigned successfully.');
    }

    public function unassign(Request $request, int $farmId)
    {
        $owner = $request->user();

        $farm = DB::table('farms')
            ->where('id', $farmId)
            ->where('owner_id', $owner->id)
            ->first();

        if (! $farm) {
            abort(403);
        }

        DB::table('farm_managers')->where('farm_id', $farmId)->delete();

        return back()->with('success', 'Manager unassigned successfully.');
    }
}
