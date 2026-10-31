<?php

namespace Tests\Unit;

use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    public function test_admin_guard_can_access_the_admin_route(): void
    {
        $request = Request::create('/admin/dashboard');
        Auth::guard('admin')->setUser((new User())->forceFill(['role' => 'admin']));

        $response = (new AdminMiddleware())->handle($request, fn () => new Response('allowed'));

        self::assertSame('allowed', $response->getContent());
    }

    public function test_staff_session_alone_is_redirected_to_admin_login(): void
    {
        $request = Request::create('/admin/dashboard');
        Auth::guard('admin')->forgetUser();
        Auth::guard('staff')->setUser((new User())->forceFill(['role' => 'staff']));

        $response = (new AdminMiddleware())->handle($request, fn () => new Response('unexpected'));

        self::assertSame(route('Admin.login'), $response->headers->get('Location'));
        self::assertSame('staff', Auth::guard('staff')->user()->role);
    }

    public function test_admin_guard_rejects_non_admin_users(): void
    {
        $request = Request::create('/admin/dashboard');
        Auth::guard('admin')->setUser((new User())->forceFill(['role' => 'accountant']));

        try {
            (new AdminMiddleware())->handle($request, fn () => new Response('unexpected'));
            self::fail('Non-admin users should not access the admin dashboard.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_admin_and_staff_guards_keep_different_users_in_the_same_session(): void
    {
        Auth::guard('staff')->setUser((new User())->forceFill(['id' => 10, 'role' => 'staff']));
        Auth::guard('admin')->setUser((new User())->forceFill(['id' => 20, 'role' => 'admin']));

        self::assertSame(10, Auth::guard('staff')->id());
        self::assertSame(20, Auth::guard('admin')->id());
    }
}
