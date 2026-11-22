<?php

namespace Tests\Feature;

use App\Http\Controllers\Staff\StaffOperations;
use App\Http\Middleware\InitializeTenantContext;
use App\Models\Ticket;
use App\Support\TenantContext;
use BaconQrCode\Renderer\Image\Svg;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicTicketTenantIsolationTest extends TestCase
{
    private const FIRST_TOKEN = 'public-ticket-token-one';

    private const SECOND_TOKEN = 'public-ticket-token-two';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('operations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('trip_date')->nullable();
            $table->string('trip_time')->nullable();
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('infants')->default(0);
            $table->decimal('amount_total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('operation_id');
            $table->string('ticket_code');
            $table->string('token')->unique();
            $table->string('status')->default('active');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });

        DB::table('operations')->insert([
            [
                'id' => 101,
                'tenant_id' => 1,
                'trip_date' => '2026-12-01',
                'trip_time' => '09:30',
                'adults' => 2,
                'children' => 0,
                'infants' => 0,
                'amount_total' => 120,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 202,
                'tenant_id' => 2,
                'trip_date' => '2026-12-02',
                'trip_time' => '10:30',
                'adults' => 3,
                'children' => 1,
                'infants' => 0,
                'amount_total' => 240,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('tickets')->insert([
            [
                'id' => 1,
                'tenant_id' => 1,
                'operation_id' => 101,
                'ticket_code' => 'TICKET-ONE',
                'token' => self::FIRST_TOKEN,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'tenant_id' => 2,
                'operation_id' => 202,
                'ticket_code' => 'TICKET-TWO',
                'token' => self::SECOND_TOKEN,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('operations');

        parent::tearDown();
    }

    public function test_valid_token_shows_ticket_in_its_tenant_and_generates_a_token_qr_link(): void
    {
        $response = $this->get(route('operation.ticket', self::FIRST_TOKEN));

        $response->assertOk()
            ->assertSee('TICKET-ONE')
            ->assertSee('2026-12-01')
            ->assertDontSee('TICKET-TWO');

        $qrCode = $this->qrCodeFromResponse($response->getContent());
        $this->assertSame($this->expectedQrCode(self::FIRST_TOKEN), $qrCode);
        $this->assertFalse(TenantContext::isInitialized());
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->get('/ticket/not-a-ticket-token')->assertNotFound();
    }

    public function test_numeric_ticket_id_is_not_a_supported_public_link(): void
    {
        $this->get('/ticket/1')->assertNotFound();
    }

    public function test_different_initialized_tenant_is_rejected_and_preserved(): void
    {
        $this->withoutMiddleware(InitializeTenantContext::class);
        TenantContext::initialize(1);

        $this->get(route('operation.ticket', self::SECOND_TOKEN))->assertNotFound();

        $this->assertTrue(TenantContext::isInitialized());
        $this->assertSame(1, TenantContext::id());
    }

    public function test_public_ticket_middleware_clears_an_uninitialized_prior_context(): void
    {
        $this->withoutMiddleware(InitializeTenantContext::class);
        TenantContext::clear();

        $this->get(route('operation.ticket', self::FIRST_TOKEN))->assertOk();

        $this->assertFalse(TenantContext::isInitialized());
        $this->assertNull(TenantContext::id());
    }

    public function test_public_ticket_middleware_restores_a_matching_initialized_context(): void
    {
        $this->withoutMiddleware(InitializeTenantContext::class);
        TenantContext::initialize(1);

        $this->get(route('operation.ticket', self::FIRST_TOKEN))->assertOk();

        $this->assertTrue(TenantContext::isInitialized());
        $this->assertSame(1, TenantContext::id());
    }

    public function test_pdf_qr_link_uses_the_ticket_token(): void
    {
        TenantContext::initialize(1);
        $ticket = Ticket::query()->where('token', self::FIRST_TOKEN)->firstOrFail();

        $pdf = \Mockery::mock(DomPdf::class);
        $pdf->shouldReceive('download')
            ->once()
            ->with('TICKET-ONE.pdf')
            ->andReturn(response('TICKET-ONE.pdf'));

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data): bool {
                $this->assertSame('staff.Operations.ticket-pdf', $view);
                $this->assertSame($this->expectedQrCode(self::FIRST_TOKEN), $data['qrCode']);

                return true;
            })
            ->andReturn($pdf);

        $response = (new StaffOperations)->ticketPdf($ticket);

        $this->assertSame('TICKET-ONE.pdf', $response->getContent());
    }

    private function qrCodeFromResponse(string $content): string
    {
        $this->assertMatchesRegularExpression(
            '/data:image\\/svg\\+xml;base64,([^"]+)/',
            $content,
        );
        preg_match('/data:image\\/svg\\+xml;base64,([^"]+)/', $content, $matches);

        return $matches[1];
    }

    private function expectedQrCode(string $token): string
    {
        $renderer = new Svg;
        $renderer->setWidth(200);
        $renderer->setHeight(200);

        return base64_encode(
            (new Writer($renderer))->writeString(route('operation.ticket', $token)),
        );
    }
}
