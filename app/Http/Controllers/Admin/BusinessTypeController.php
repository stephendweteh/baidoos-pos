<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use Illuminate\Http\Request;

class BusinessTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:owner']);
    }

    public function index()
    {
        $types = BusinessType::withCount('branches')->latest()->get();
        return view('admin.business-types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.business-types.form', ['type' => new BusinessType()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:business_types,name',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'sometimes|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        BusinessType::create($data);
        return redirect()->route('admin.business-types.index')
            ->with('success', 'Business category created.');
    }

    public function edit(BusinessType $businessType)
    {
        return view('admin.business-types.form', ['type' => $businessType]);
    }

    public function update(Request $request, BusinessType $businessType)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:business_types,name,' . $businessType->id,
            'description' => 'nullable|string|max:255',
            'is_active'   => 'sometimes|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $businessType->update($data);
        return redirect()->route('admin.business-types.index')
            ->with('success', 'Business category updated.');
    }

    public function destroy(BusinessType $businessType)
    {
        if ($businessType->branches()->exists()) {
            return back()->with('error', 'Cannot delete: branches are using this category.');
        }
        $businessType->delete();
        return redirect()->route('admin.business-types.index')
            ->with('success', 'Business category deleted.');
    }
}
