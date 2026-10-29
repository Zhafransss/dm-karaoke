<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** User and role management, Super Admin only (FR-ADM-16). */
class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', ['users' => User::orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['u' => new User(['role' => 'admin_reservasi', 'active' => true])]);
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['u' => $user]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new User);
    }

    public function update(Request $request, User $user)
    {
        return $this->save($request, $user);
    }

    public function toggle(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'Tidak bisa menonaktifkan akun sendiri.');
        $user->update(['active' => ! $user->active]);

        return back()->with('ok', $user->name.($user->active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'Tidak bisa menghapus akun sendiri.');
        $user->delete();

        return redirect()->route('admin.pengguna.index')->with('ok', 'Pengguna dihapus.');
    }

    private function save(Request $request, User $u)
    {
        $self = $u->exists && $u->is($request->user());
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'regex:/^[a-z0-9._]{3,20}$/', Rule::unique('users', 'username')->ignore($u->id)],
            'role' => [$self ? 'nullable' : 'required', Rule::in(array_keys(config('dmk.roles')))],
            'password' => [$u->exists ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
        ], [
            'name.required' => 'Nama wajib diisi.', 'username.regex' => '3–20 karakter: huruf kecil, angka, titik, atau garis bawah.',
            'username.unique' => 'Username sudah dipakai, pilih yang lain.', 'password.*' => 'Kata sandi minimal 8 karakter.',
        ]);
        $u->fill(['name' => $data['name'], 'username' => $data['username']]);
        if (! $self) {
            // Super Admin cannot demote or deactivate themselves (avoids locking everyone out).
            $u->role = $data['role'];
            $u->active = $request->boolean('active');
        }
        if (! empty($data['password'])) {
            $u->password = $data['password'];
        }
        $u->save();

        return redirect()->route('admin.pengguna.index')->with('ok', $u->wasRecentlyCreated ? 'Pengguna ditambahkan.' : 'Pengguna diperbarui.');
    }
}
