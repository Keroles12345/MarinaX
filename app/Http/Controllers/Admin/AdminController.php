<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\TenantContext;

class AdminController extends Controller
{
    public function showLogin()
    {


        return view('admin.auth.login');
    }
    public function showdashboard(){
    return redirect()->route('accounting.home');
}
public function loginn(Request $request)
{
    TenantContext::clear();

    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $guard = Auth::guard('admin');
    if (!$guard->attempt($credentials, $request->boolean('remember'))) {
        return back()
            ->withInput($request->except('password'))
            ->withErrors([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            ]);
    }

    $request->session()->regenerate();

    $user = $guard->user();

    if ($user->is_super_admin) {
        $guard->logout();
        $request->session()->regenerate();

        return redirect()->route('superadmin.login');
    }

    if (! $user->status) {
        $guard->logout();
        $request->session()->regenerate();

        return back()
            ->withInput($request->except('password'))
            ->withErrors(['error' => 'هذا الحساب موقوف']);
    }

    if (! $user->is_super_admin && (! $user->tenant_id || ! $user->tenant?->status)) {
        $guard->logout();
        $request->session()->regenerate();

        return back()
            ->withInput($request->except('password'))
            ->withErrors(['error' => 'الشركة المرتبط بها المستخدم غير مفعلة']);
    }

    if (! in_array($user->role, ['admin', 'accountant', 'viewer', 'account_user'], true)) {
        $guard->logout();
        $request->session()->regenerate();

        return back()
            ->withInput($request->except('password'))
            ->withErrors([
                'error' => 'هذا المستخدم ليس لديه صلاحية دخول النظام المحاسبي',
            ]);
    }

    Auth::shouldUse('admin');

    DB::table('audit_log')->insert([
        'user' => $user->name,
        'action' => 'تسجيل دخول',
        'details' => 'دخول إلى النظام المحاسبي',
        'tenant_id' => $user->tenant_id,
        'at' => now(),
    ]);

    if ($user->is_super_admin) {
        return redirect()->route('superadmin.dashboard');
    }

    return redirect()->route('accounting.home');
}
}
