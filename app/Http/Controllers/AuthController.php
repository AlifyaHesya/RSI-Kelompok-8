<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\KodeAkses;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/** UC-01 Registrasi & Login, UC-02 Logout. */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($kredensial, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Halaman pilih role (MHS / PIC / ADM). */
    public function pilihRole()
    {
        return view('auth.pilih-role', ['roles' => Role::cases()]);
    }

    /** Validasi kode akses untuk role PIC / ADM sebelum formulir registrasi dibuka. */
    public function cekKodeAkses(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:PIC,ADM'],
            'kode_akses' => ['required', 'string'],
        ]);

        $valid = KodeAkses::where('role', $data['role'])
            ->where('kode', $data['kode_akses'])
            ->where('aktif', true)
            ->exists();

        if (! $valid) {
            return back()
                ->withErrors(['kode_akses' => 'Kode akses '.$data['role'].' tidak valid'])
                ->withInput();
        }

        $request->session()->put('kode_akses_valid', $data['role']);

        return redirect()->route('register.form', $data['role']);
    }

    public function showRegister(Request $request, string $role)
    {
        $roleEnum = Role::tryFrom($role) ?? abort(404);

        if (! $this->boleMendaftar($request, $roleEnum)) {
            return redirect()->route('register')
                ->withErrors(['kode_akses' => 'Masukkan kode akses terlebih dahulu']);
        }

        return view('auth.register', ['role' => $roleEnum]);
    }

    public function register(Request $request, string $role): RedirectResponse
    {
        $roleEnum = Role::tryFrom($role) ?? abort(404);

        if (! $this->boleMendaftar($request, $roleEnum)) {
            return redirect()->route('register')
                ->withErrors(['kode_akses' => 'Masukkan kode akses terlebih dahulu']);
        }

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        if ($roleEnum === Role::Mahasiswa) {
            $rules['nim'] = ['required', 'string', 'max:20', 'unique:mahasiswa,nim'];
            $rules['program_studi'] = ['required', 'string', 'max:100'];
        }

        $data = $request->validate($rules);

        DB::transaction(function () use ($data, $roleEnum) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // di-hash otomatis oleh cast 'hashed'
                'role' => $roleEnum,
            ]);

            if ($roleEnum === Role::Mahasiswa) {
                $user->mahasiswa()->create([
                    'nim' => $data['nim'],
                    'program_studi' => $data['program_studi'],
                ]);
            } elseif ($roleEnum === Role::Pic) {
                $user->pic()->create([]);
            }
        });

        $request->session()->forget('kode_akses_valid');

        return redirect()->route('login')->with('status', 'Registrasi berhasil');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function boleMendaftar(Request $request, Role $role): bool
    {
        return $role === Role::Mahasiswa
            || $request->session()->get('kode_akses_valid') === $role->value;
    }
}
