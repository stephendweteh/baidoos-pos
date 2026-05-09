<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchStaff;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $branchId = $user->isCashier() ? $user->branch_id : $request->get('branch_id');

        $branches = $user->isOwner() || $user->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        $staff = BranchStaff::with('branch')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        return view('staff.index', compact('staff', 'branches', 'branchId'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $branchId = $user->isCashier() ? $user->branch_id : $request->input('branch_id');

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (!$branchId) {
            return back()->withErrors(['branch_id' => 'Select a branch before adding staff.'])->withInput();
        }

        BranchStaff::create([
            'branch_id' => $branchId,
            'name' => $data['name'],
            'is_active' => true,
        ]);

        return redirect()->route('staff.index', ($user->isOwner() || $user->isSuperAdmin()) ? ['branch_id' => $branchId] : [])
            ->with('success', 'Staff added.');
    }

    public function update(Request $request, BranchStaff $staff)
    {
        $user = auth()->user();

        if ($user->isCashier() && $staff->branch_id !== $user->branch_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $staff->update([
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Staff updated.');
    }

    public function destroy(BranchStaff $staff)
    {
        $user = auth()->user();

        if ($user->isCashier() && $staff->branch_id !== $user->branch_id) {
            abort(403);
        }

        $staff->delete();

        return back()->with('success', 'Staff removed.');
    }
}