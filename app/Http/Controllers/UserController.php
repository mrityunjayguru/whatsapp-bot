<?php

namespace App\Http\Controllers;

use App\Mail\UserWelcomeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    // Only sub-users created by this company
    private function ownUsers()
    {
        return User::where('created_by', auth()->id())
            ->whereNotIn('role_id', [1, 2]); // exclude superadmin & company roles
    }

    // Only custom roles created by this company
    private function ownRoles()
    {
        return Role::where('created_by', auth()->id())
            ->whereNotIn('name', Role::SYSTEM_ROLES)
            ->orderBy('display_name')
            ->get();
    }

    public function index(): View
    {
        $users = $this->ownUsers()
            ->with('role')
            ->latest()
            ->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = $this->ownRoles();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id'  => ['required', 'exists:roles,id'],
            'status'   => ['required', 'in:0,1'],
        ], [
            'name.required'     => 'Name is required.',
            'email.required'    => 'Email is required.',
            'email.unique'      => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.confirmed'=> 'Password confirmation does not match.',
            'role_id.required'  => 'Role is required.',
            'status.required'   => 'Status is required.',
        ]);

        // Ensure role belongs to this company
        $role = Role::where('id', $request->role_id)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => $request->password,
            'role_id'    => $role->id,
            'status'     => $request->status,
            'created_by' => auth()->id(),
        ]);

        // Send welcome email with login credentials
        try {
            Mail::to($user->email)->send(new UserWelcomeMail($user, $request->password));
        } catch (\Throwable $e) {
            logger()->error('Welcome mail failed: ' . $e->getMessage());
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully. A welcome email has been sent.');
    }

    public function edit(User $user): View
    {
        abort_if($user->created_by !== auth()->id(), 403);

        $roles = $this->ownRoles();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->created_by !== auth()->id(), 403);

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id'  => ['required', 'exists:roles,id'],
            'status'   => ['required', 'in:0,1'],
        ], [
            'name.required'    => 'Name is required.',
            'email.required'   => 'Email is required.',
            'email.unique'     => 'This email is already registered.',
            'password.min'     => 'Password must be at least 8 characters.',
            'password.confirmed'=> 'Password confirmation does not match.',
            'role_id.required' => 'Role is required.',
            'status.required'  => 'Status is required.',
        ]);

        $role = Role::where('id', $request->role_id)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $payload = [
            'name'    => $request->name,
            'email'   => $request->email,
            'role_id' => $role->id,
            'status'  => $request->status,
        ];

        if ($request->filled('password')) {
            $payload['password'] = $request->password;
        }

        $user->update($payload);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->created_by !== auth()->id(), 403);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
