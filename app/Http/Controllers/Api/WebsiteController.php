<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentProviderNotConfigured;
use App\Http\Controllers\Controller;
use App\Models\Companies;
use App\Models\Currencies;
use App\Models\customers;
use App\Models\operations;
use App\Models\Trips;
use App\Models\Trips_hotels;
use App\Models\User;
use App\Services\BookingLifecycleService;
use App\Services\BookingNotificationDispatcher;
use App\Services\PaymentService;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebsiteController extends Controller
{
    public function trips(): JsonResponse
    {
        $trips = Trips::query()
            ->where('status', true)
            ->with([
                'region',
                'images',
                'tripHotels' => fn ($query) => $query
                    ->where('status', true)
                    ->whereHas('hotel', fn ($hotelQuery) => $hotelQuery->where('status', true))
                    ->with('hotel'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Trips $trip): array => $this->tripData($trip));

        return response()->json(['data' => $trips]);
    }

    public function trip(string $trip): JsonResponse
    {
        $tripModel = Trips::query()
            ->where('status', true)
            ->with([
                'region',
                'images',
                'tripHotels' => fn ($query) => $query
                    ->where('status', true)
                    ->whereHas('hotel', fn ($hotelQuery) => $hotelQuery->where('status', true))
                    ->with('hotel'),
            ])
            ->findOrFail($trip);

        return response()->json(['data' => $this->tripData($tripModel)]);
    }

    public function companies(): JsonResponse
    {
        $companies = Companies::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json(['data' => $companies]);
    }

    public function currencies(): JsonResponse
    {
        $currencies = Currencies::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'symbol', 'exchange_rate', 'is_default']);

        return response()->json(['data' => $currencies]);
    }

    public function storeBooking(
        Request $request,
        BookingNotificationDispatcher $notifications,
    ): JsonResponse {
        Validator::make(
            ['idempotency_key' => $request->header('Idempotency-Key')],
            ['idempotency_key' => ['nullable', 'string', 'min:8', 'max:255']],
        )->validate();
        $idempotencyKey = $request->header('Idempotency-Key');
        $tenantId = (int) TenantContext::id();
        $keyHash = is_string($idempotencyKey)
            ? hash_hmac('sha256', $idempotencyKey, (string) config('app.key'))
            : null;
        $requestHash = hash('sha256', json_encode($this->canonicalize($request->all()), JSON_THROW_ON_ERROR));

        if ($keyHash) {
            $existing = DB::table('tenant_api_idempotency_keys')
                ->where('tenant_id', $tenantId)
                ->where('key_hash', $keyHash)
                ->first();
            if ($existing) {
                return $this->idempotencyReplay($existing, $requestHash);
            }
        }

        $data = $request->validate([
            'trip_id' => [
                'required',
                'integer',
                Rule::exists('trips', 'id')->where('tenant_id', TenantContext::id())->where('status', true),
            ],
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where('tenant_id', TenantContext::id())->where('status', true),
            ],
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],
            'hotel_id' => ['nullable', 'integer'],
            'trip_date' => ['required', 'date', 'after_or_equal:today'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:50'],
            'customer.address' => ['nullable', 'string', 'max:255'],
            'adults' => ['required', 'integer', 'min:0', 'max:100'],
            'children' => ['required', 'integer', 'min:0', 'max:100'],
            'infants' => ['required', 'integer', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        if ((int) $data['adults'] + (int) $data['children'] + (int) $data['infants'] < 1) {
            return response()->json(['message' => 'يجب تحديد مسافر واحد على الأقل.'], 422);
        }

        $trip = Trips::query()->where('status', true)->findOrFail($data['trip_id']);
        $company = Companies::query()->where('status', true)->findOrFail($data['company_id']);
        $currency = Currencies::query()->where('is_active', true)->findOrFail($data['currency_id']);
        $hotel = null;
        if (! empty($data['hotel_id'])) {
            $hotel = Trips_hotels::query()
                ->where('trip_id', $trip->id)
                ->where('status', true)
                ->whereHas('hotel', fn ($query) => $query->where('status', true))
                ->with('hotel')
                ->findOrFail($data['hotel_id']);
        }

        $prices = TenantDatabase::table('prices_company as pc')
            ->where('pc.id_company', $company->id)
            ->where('pc.id_trip', $trip->id)
            ->where('pc.id_currency', $currency->id)
            ->first(['pc.price_adult', 'pc.price_child', 'pc.price_infant']);

        if (! $prices) {
            return response()->json(['message' => 'لا توجد أسعار مضبوطة لهذه الرحلة والشركة والعملة.'], 422);
        }

        $creator = User::withoutGlobalScopes()
            ->where('tenant_id', TenantContext::id())
            ->where('status', true)
            ->where('is_super_admin', false)
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->first();

        if (! $creator) {
            return response()->json(['message' => 'لا يوجد مستخدم نشط مرتبط بالشركة لتسجيل الحجز.'], 503);
        }

        $amount = round(
            ((int) $data['adults'] * (float) $prices->price_adult)
            + ((int) $data['children'] * (float) $prices->price_child)
            + ((int) $data['infants'] * (float) $prices->price_infant),
            2,
        );
        try {
            $result = DB::transaction(function () use ($data, $trip, $company, $currency, $hotel, $prices, $creator, $amount, $tenantId, $keyHash, $requestHash): array {
                if ($keyHash) {
                    DB::table('tenant_api_idempotency_keys')->insert([
                        'tenant_id' => $tenantId,
                        'key_hash' => $keyHash,
                        'request_hash' => $requestHash,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $customerData = $data['customer'];
                $customer = customers::query()->create([
                    'name' => $customerData['name'],
                    'email' => $customerData['email'] ?? null,
                    'phone' => $customerData['phone'],
                    'address' => $customerData['address'] ?? null,
                ]);

                $operation = operations::query()->create([
                    'booking_id' => 'WEB-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                    'id_trip' => $trip->id,
                    'id_company' => $company->id,
                    'customer_id' => $customer->id,
                    'trip_hotel_id' => $hotel?->id,
                    'id_currency' => $currency->id,
                    'trip_date' => $data['trip_date'],
                    'trip_time' => $trip->time_trip,
                    'time_pickup' => $hotel?->pickup_time,
                    'adults' => $data['adults'],
                    'children' => $data['children'],
                    'infants' => $data['infants'],
                    'price_adult' => $prices->price_adult,
                    'price_child' => $prices->price_child,
                    'price_infant' => $prices->price_infant,
                    'subtotal' => $amount,
                    'amount_total' => $amount,
                    'totalamount' => $amount,
                    'amount_remaining' => $amount,
                    'status_payment' => 'pending',
                    'status' => 'pending',
                    'booking_status' => 'pending',
                    'remarks' => $data['remarks'] ?? null,
                    'by_created' => $creator->id,
                ]);
                DB::table('operation_status_history')->insert([
                    'tenant_id' => $tenantId,
                    'operation_id' => $operation->id,
                    'from_status' => null,
                    'to_status' => 'pending',
                    'changed_by' => null,
                    'reason' => 'Booking created through the tenant website API.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $responseBody = [
                    'data' => [
                        'booking_id' => $operation->booking_id,
                        'status' => $operation->booking_status,
                        'amount' => $operation->amount_total,
                        'currency' => $currency->code,
                    ],
                ];

                if ($keyHash) {
                    DB::table('tenant_api_idempotency_keys')
                        ->where('tenant_id', $tenantId)
                        ->where('key_hash', $keyHash)
                        ->update([
                            'response_body' => json_encode($responseBody, JSON_THROW_ON_ERROR),
                            'response_status' => 201,
                            'operation_id' => $operation->id,
                            'completed_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                return ['operation' => $operation, 'body' => $responseBody, 'status' => 201];
            });
        } catch (QueryException $exception) {
            if (! $keyHash || ! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }

            $existing = DB::table('tenant_api_idempotency_keys')
                ->where('tenant_id', $tenantId)
                ->where('key_hash', $keyHash)
                ->first();
            if (! $existing) {
                throw $exception;
            }

            return $this->idempotencyReplay($existing, $requestHash);
        }

        $notifications->dispatch($result['operation'], 'created', 'A new booking is pending confirmation.');

        return response()->json($result['body'], $result['status']);
    }

    public function booking(string $booking): JsonResponse
    {
        $operation = operations::query()->where('booking_id', $booking)->firstOrFail();

        return response()->json([
            'data' => [
                'booking_id' => $operation->booking_id,
                'status' => $operation->booking_status,
                'trip_id' => $operation->id_trip,
                'company_id' => $operation->id_company,
                'trip_date' => $operation->trip_date?->toDateString(),
                'adults' => $operation->adults,
                'children' => $operation->children,
                'infants' => $operation->infants,
                'amount' => $operation->amount_total,
                'amount_paid' => $operation->amount_paid,
                'amount_remaining' => $operation->amount_remaining,
                'payment_status' => $operation->status_payment,
                'created_at' => $operation->created_at,
            ],
        ]);
    }

    public function updateBookingStatus(
        Request $request,
        string $booking,
        BookingLifecycleService $lifecycle,
        BookingNotificationDispatcher $notifications,
    ): JsonResponse {
        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'paid', 'cancelled', 'refunded'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $operation = operations::query()->where('booking_id', $booking)->firstOrFail();
        $operation = $lifecycle->transition(
            (int) $operation->id,
            (int) TenantContext::id(),
            $data['status'],
            null,
            $data['reason'] ?? null,
            'api_key',
        );
        $notifications->dispatch($operation, $data['status'], 'Booking status changed to '.$data['status'].'.');

        return response()->json(['data' => [
            'booking_id' => $operation->booking_id,
            'status' => $operation->booking_status,
        ]]);
    }

    public function createPayment(Request $request, string $booking, PaymentService $payments): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);
        Validator::make(
            ['idempotency_key' => $request->header('Idempotency-Key')],
            ['idempotency_key' => ['required', 'string', 'min:8', 'max:255']],
        )->validate();

        try {
            $result = $payments->createIntent($booking, (float) $data['amount'], $request->header('Idempotency-Key'));
        } catch (PaymentProviderNotConfigured) {
            return response()->json(['message' => 'لا يوجد مزود دفع مهيأ لهذا النظام.'], 503);
        }

        return response()->json([
            'data' => [
                'payment_id' => $result['payment']->id,
                'status' => $result['payment']->status,
                'redirect_url' => $result['redirect_url'],
            ],
        ], 202);
    }

    public function refundPayment(Request $request, string $payment, PaymentService $payments): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);
        Validator::make(
            ['idempotency_key' => $request->header('Idempotency-Key')],
            ['idempotency_key' => ['required', 'string', 'min:8', 'max:255']],
        )->validate();

        try {
            $payments->refund((int) $payment, (float) $data['amount'], $request->header('Idempotency-Key'));
        } catch (PaymentProviderNotConfigured) {
            return response()->json(['message' => 'لا يوجد مزود دفع مهيأ لهذا النظام.'], 503);
        }

        return response()->json(['data' => ['payment_id' => (int) $payment, 'status' => 'refund_pending']], 202);
    }

    private function idempotencyReplay(object $existing, string $requestHash): JsonResponse
    {
        if (! hash_equals($existing->request_hash, $requestHash)) {
            return response()->json([
                'message' => 'تم استخدام Idempotency-Key سابقاً مع محتوى مختلف.',
            ], 409);
        }

        if (! $existing->response_body || ! $existing->response_status) {
            return response()->json(['message' => 'الطلب بهذا المفتاح قيد المعالجة. أعد المحاولة لاحقاً.'], 409);
        }

        return response()->json(
            json_decode($existing->response_body, true, flags: JSON_THROW_ON_ERROR),
            (int) $existing->response_status,
            ['Idempotent-Replay' => 'true'],
        );
    }

    private function canonicalize(array $data): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->canonicalize($value);
            }
        }
        unset($value);

        if (! array_is_list($data)) {
            ksort($data);
        }

        return $data;
    }

    private function tripData(Trips $trip): array
    {
        $images = $trip->images
            ->map(fn ($image): string => Storage::disk('public')->url($image->path))
            ->values();
        $prices = TenantDatabase::table('prices_company as pc')
            ->join('companies as c', function ($join): void {
                $join->on('c.id', '=', 'pc.id_company')
                    ->where('c.tenant_id', TenantContext::id())
                    ->where('c.status', true);
            })
            ->join('currencies as cur', function ($join): void {
                $join->on('cur.id', '=', 'pc.id_currency')
                    ->where('cur.is_active', true);
            })
            ->where('pc.id_trip', $trip->id)
            ->orderBy('c.name')
            ->get([
                'c.id as company_id',
                'c.name as company_name',
                'cur.id as currency_id',
                'cur.code as currency_code',
                'cur.symbol as currency_symbol',
                'pc.price_adult',
                'pc.price_child',
                'pc.price_infant',
            ]);

        return [
            'id' => $trip->id,
            'name' => $trip->name,
            'description' => $trip->description,
            'time' => $trip->time_trip?->format('H:i'),
            'region' => $trip->region?->name,
            'includes' => $trip->includes ?? [],
            'excludes' => $trip->excludes ?? [],
            'faqs' => $trip->faqs ?? [],
            'primary_image' => $images->first(),
            'images' => $images,
            'hotels' => $trip->tripHotels
                ->where('status', true)
                ->map(fn (Trips_hotels $tripHotel): array => [
                    'id' => $tripHotel->id,
                    'name' => $tripHotel->hotel?->name,
                    'pickup_time' => $tripHotel->pickup_time,
                ])
                ->values(),
            'prices' => $prices,
        ];
    }
}
