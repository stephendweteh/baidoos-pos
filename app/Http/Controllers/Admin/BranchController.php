<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BusinessType;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:owner']);
    }

    public function index()
    {
        $branches = Branch::with('businessType')->withCount('users', 'items')->latest()->get();
        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        $businessTypes = BusinessType::where('is_active', true)->get();
        return view('admin.branches.form', [
            'branch'        => new Branch(),
            'businessTypes' => $businessTypes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'business_type_id' => 'required|exists:business_types,id',
            'address'          => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'is_active'        => 'sometimes|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Branch::create($data);
        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch created.');
    }

    public function edit(Branch $branch)
    {
        $businessTypes = BusinessType::where('is_active', true)->get();
        return view('admin.branches.form', compact('branch', 'businessTypes'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'business_type_id' => 'required|exists:business_types,id',
            'address'          => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'is_active'        => 'sometimes|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $branch->update($data);
        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->sales()->exists()) {
            return back()->with('error', 'Cannot delete: this branch has sales records.');
        }
        $branch->delete();
        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch deleted.');
    }
}
