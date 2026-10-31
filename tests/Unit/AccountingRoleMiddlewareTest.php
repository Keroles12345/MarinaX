<?php

namespace Tests\Unit;

use App\Http\Middleware\AccountingRoleMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AccountingRoleMiddlewareTest extends TestCase
{
    public function test_account_user_can_use_accounting_pages_and_write_routes(): void
    {
        $middleware = new AccountingRoleMiddleware();

        foreach ([
            ['GET', '/accounts'],
            ['GET', '/accounts/tree'],
            ['GET', '/api/accounts'],
            ['GET', '/api/accounts/tree'],
            ['GET', '/accounting'],
            ['GET', '/journal'],
            ['GET', '/accounts/export.xlsx'],
            ['POST', '/api/accounts'],
            ['DELETE', '/api/accounts/1'],
            ['POST', '/api/journal'],
        ] as [$method, $path]) {
            $request = Request::create($path, $method);
            Auth::guard('admin')->setUser((new User())->forceFill(['role' => 'account_user']));
            $response = $middleware->handle(
                $request,
                fn () => new Response('allowed'),
                'admin',
                'accountant',
                'account_user',
            );

            self::assertSame('allowed', $response->getContent(), "{$method} {$path} should be allowed.");
        }
    }

    public function test_account_user_remains_denied_from_admin_only_routes(): void
    {
        $middleware = new AccountingRoleMiddleware();

        foreach ([
            ['GET', '/users'],
            ['GET', '/audit'],
            ['POST', '/api/accounts/wipe'],
            ['POST', '/api/users'],
        ] as [$method, $path]) {
            $request = Request::create($path, $method);
            Auth::guard('admin')->setUser((new User())->forceFill(['role' => 'account_user']));

            try {
                $middleware->handle($request, fn () => new Response('unexpected'), 'admin');
                self::fail("{$method} {$path} should be denied.");
            } catch (HttpException $exception) {
                self::assertSame(403, $exception->getStatusCode());
            }
        }
    }
}
