<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountingController;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

class AccountingBackupTenantIsolationTest extends TestCase
{
    private const BACKUP_TABLES = [
        'users',
        'accounts',
        'regions',
        'journal_entries',
        'journal_lines',
        'settings',
        'audit_log',
        'employee_custody_deposits',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::BACKUP_TABLES as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
            });
        }

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        foreach (self::BACKUP_TABLES as $tableName) {
            Schema::dropIfExists($tableName);
        }

        parent::tearDown();
    }

    public function test_same_named_backup_download_is_limited_to_the_active_tenant(): void
    {
        Storage::disk('local')->put('backups/tenants/1/manual-shared.json', 'tenant one');
        Storage::disk('local')->put('backups/tenants/2/manual-shared.json', 'tenant two');
        TenantContext::initialize(2);

        $response = (new AccountingController())->downloadBackup('manual-shared.json');
        ob_start();
        $response->sendContent();
        $contents = ob_get_clean();

        $this->assertSame('tenant two', $contents);
    }

    public function test_legacy_root_backups_are_neither_listed_nor_downloaded_or_restored(): void
    {
        Storage::disk('local')->put('backups/manual-legacy.json', 'legacy backup');
        Storage::disk('local')->put('backups/tenants/1/manual-current.json', 'tenant backup');
        TenantContext::initialize(1);
        $controller = new AccountingController();

        $this->assertSame(
            ['manual-current.json'],
            collect($controller->backups()->getData(true)['backups'])->pluck('name')->all(),
        );

        $this->assertBackupRequestNotFound(fn () => $controller->downloadBackup('manual-legacy.json'));
        $this->assertBackupRequestNotFound(fn () => $controller->restoreBackup('manual-legacy.json'));
        Storage::disk('local')->assertExists('backups/manual-legacy.json');
        $this->assertSame('legacy backup', Storage::disk('local')->get('backups/manual-legacy.json'));
        $this->assertSame(['backups/tenants/1/manual-current.json'], Storage::disk('local')->files('backups/tenants/1'));
    }

    public function test_retention_keeps_24_current_tenant_backups_without_touching_other_paths(): void
    {
        Storage::disk('local')->put('backups/manual-legacy.json', 'legacy backup');
        Storage::disk('local')->put('backups/tenants/2/manual-other.json', 'other tenant');
        for ($index = 1; $index <= 24; $index++) {
            Storage::disk('local')->put(
                'backups/tenants/1/manual-old-'.$index.'.json',
                'old tenant backup',
            );
        }
        TenantContext::initialize(1);

        $method = new \ReflectionMethod(AccountingController::class, 'storeBackup');
        $method->invoke(new AccountingController(), 'manual-new.json');

        $tenantFiles = Storage::disk('local')->files('backups/tenants/1');
        $this->assertCount(24, $tenantFiles);
        $this->assertSame(['backups/tenants/2/manual-other.json'], Storage::disk('local')->files('backups/tenants/2'));
        $this->assertSame(['backups/manual-legacy.json'], Storage::disk('local')->files('backups'));
        $this->assertSame('legacy backup', Storage::disk('local')->get('backups/manual-legacy.json'));
    }

    public function test_backup_endpoints_fail_closed_without_an_initialized_non_null_tenant(): void
    {
        $controller = new AccountingController();

        TenantContext::clear();
        $this->assertBackupRequestForbidden(fn () => $controller->backups());
        $this->assertBackupRequestForbidden(fn () => $controller->createBackup());
        $this->assertBackupRequestForbidden(fn () => $controller->downloadBackup('manual-any.json'));
        $this->assertBackupRequestForbidden(fn () => $controller->restoreBackup('manual-any.json'));
        $this->assertBackupRequestForbidden(fn () => $controller->restoreUploadedBackup(Request::create('/api/restore-file', 'POST')));

        TenantContext::initialize(null);
        $this->assertBackupRequestForbidden(fn () => $controller->backups());
        $this->assertBackupRequestForbidden(fn () => $controller->createBackup());
        $this->assertBackupRequestForbidden(fn () => $controller->downloadBackup('manual-any.json'));
        $this->assertBackupRequestForbidden(fn () => $controller->restoreBackup('manual-any.json'));
        $this->assertBackupRequestForbidden(fn () => $controller->restoreUploadedBackup(Request::create('/api/restore-file', 'POST')));

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_cross_tenant_snapshots_are_rejected_before_backup_or_database_changes(): void
    {
        DB::table('users')->insert(['id' => 1, 'tenant_id' => 1]);
        TenantContext::initialize(1);
        $snapshot = [
            'format' => 'smartaccounting-mysql-v1',
            'tables' => array_fill_keys(self::BACKUP_TABLES, []),
        ];
        $snapshot['tables']['users'] = [['id' => 2, 'tenant_id' => 2]];
        $contents = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $controller = new AccountingController();
        $upload = UploadedFile::fake()->createWithContent('cross-tenant.json', $contents);

        $this->assertSnapshotRejected(fn () => $controller->restoreUploadedBackup(
            Request::create('/api/restore-file', 'POST', [], [], ['file' => $upload]),
        ));
        $this->assertSame([], Storage::disk('local')->allFiles());

        Storage::disk('local')->put('backups/tenants/1/manual-cross-tenant.json', $contents);
        $this->assertSnapshotRejected(fn () => $controller->restoreBackup('manual-cross-tenant.json'));
        $this->assertSame(['backups/tenants/1/manual-cross-tenant.json'], Storage::disk('local')->allFiles());
        $this->assertSame([1], DB::table('users')->pluck('tenant_id')->all());
    }

    private function assertSnapshotRejected(callable $restore): void
    {
        try {
            $restore();
            $this->fail('Expected a cross-tenant snapshot to fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
    }

    private function assertBackupRequestNotFound(callable $request): void
    {
        try {
            $request();
            $this->fail('Expected backup lookup to return not found.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    private function assertBackupRequestForbidden(callable $request): void
    {
        try {
            $request();
            $this->fail('Expected backup operation to be forbidden without a tenant.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
