<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:owner']);
    }

    public function index(Request $request)
    {
        $branchFilter = $request->get('branch_id');
        $items = Item::with('branch')
            ->when($branchFilter, fn ($q) => $q->where('branch_id', $branchFilter))
            ->latest()
            ->get();
        $branches = Branch::where('is_active', true)->get();
        return view('admin.items.index', compact('items', 'branches', 'branchFilter'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('admin.items.form', ['item' => new Item(), 'branches' => $branches]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name'      => 'required|string|max:100',
            'price'     => 'required|numeric|min:0',
            'type'      => 'required|in:service,product',
            'assign_staff' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['assign_staff'] = $request->input('type') === 'service'
            ? $request->boolean('assign_staff')
            : false;
        $data['is_active'] = $request->boolean('is_active', true);
        Item::create($data);
        return redirect()->route('admin.items.index')
            ->with('success', 'Item created.');
    }

    public function edit(Item $item)
    {
        $branches = Branch::where('is_active', true)->get();
        return view('admin.items.form', compact('item', 'branches'));
    }

    public function update(Request $request, Item $item)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name'      => 'required|string|max:100',
            'price'     => 'required|numeric|min:0',
            'type'      => 'required|in:service,product',
            'assign_staff' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['assign_staff'] = $request->input('type') === 'service'
            ? $request->boolean('assign_staff')
            : false;
        $data['is_active'] = $request->boolean('is_active', true);
        $item->update($data);
        return redirect()->route('admin.items.index')
            ->with('success', 'Item updated.');
    }

    public function destroy(Item $item)
    {
        $item->update(['is_active' => false]);
        return redirect()->route('admin.items.index')
            ->with('success', 'Item deactivated.');
    }
}
