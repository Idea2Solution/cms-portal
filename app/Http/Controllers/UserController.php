<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Followup;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Sales team logins, managed by admins from Settings. */
class UserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:users,name'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        User::create($data + ['active' => true]);

        return redirect()->route('settings.index')->with('ok', "{$data['name']} can now log in with {$data['email']}");
    }

    public function edit(User $user): View
    {
        return view('settings.user', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('users', 'name')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'active' => ['boolean'],
        ]);
        $data['active'] = $request->boolean('active');

        if ($user->is($request->user()) && ($data['role'] !== 'admin' || ! $data['active'])) {
            return back()->withInput()->with('err', 'You cannot remove your own admin access or deactivate yourself');
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data) {
            $oldName = $user->name;
            $user->update($data);

            // records store the owner by name, so carry a rename over to them
            if ($oldName !== $user->name) {
                foreach ([Company::class, Inquiry::class, Followup::class] as $model) {
                    $model::where('agent', $oldName)->update(['agent' => $user->name]);
                }
            }
        });

        return redirect()->route('settings.index')->with('ok', "{$user->name} updated");
    }

    public function account(): View
    {
        return view('settings.account', ['user' => auth()->user()]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('ok', 'Password changed');
    }
}
