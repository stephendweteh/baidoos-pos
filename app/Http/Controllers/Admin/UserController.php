<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:owner']);
    }

    protected function canManageRoles(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function index()
    {
        $users = User::with('branch')->latest()->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('admin.users.form', ['user' => new User(), 'branches' => $branches]);
    }

    public function store(Request $request)
    {
        $canManageRoles = $this->canManageRoles();

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:20',
            'password'  => 'required|string|min:6|confirmed',
            'branch_id' => 'nullable|exists:branches,id',
            'role'      => $canManageRoles
                ? 'required|in:owner,cashier,superadmin'
                : 'required|in:owner,cashier',
        ]);

        if ($data['role'] === 'cashier' && empty($data['branch_id'])) {
            return back()->withErrors(['branch_id' => 'Cashier must be assigned to a branch.'])->withInput();
        }

        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('admin.users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $branches = Branch::where('is_active', true)->get();
        return view('admin.users.form', compact('user', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $canManageRoles = $this->canManageRoles();

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:6|confirmed',
            'branch_id' => 'nullable|exists:branches,id',
            'role'      => $canManageRoles
                ? 'required|in:owner,cashier,superadmin'
                : 'sometimes|in:owner,cashier,superadmin',
        ]);

        if (!$canManageRoles) {
            $data['role'] = $user->role;
        }

        if ($data['role'] === 'cashier' && empty($data['branch_id'])) {
            return back()->withErrors(['branch_id' => 'Cashier must be assigned to a branch.'])->withInput();
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return redirect()->route('admin.users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted.');
    }
}
