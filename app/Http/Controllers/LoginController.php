<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->to($this->panelUrl($request->user()));
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'El correo o la contraseña no son correctos.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->to($this->panelUrl($request->user()));
    }

    private function panelUrl(User $user): string
    {
        return match ($user->role) {
            'admin' => '/admin',
            'instructor' => '/instructor',
            'student' => '/student',
            default => abort(403, 'Tu cuenta no tiene un rol válido.'),
        };
    }
}
