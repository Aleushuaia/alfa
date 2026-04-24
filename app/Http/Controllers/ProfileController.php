<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Cambiar contraseña del usuario autenticado.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        Auth::user()->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
