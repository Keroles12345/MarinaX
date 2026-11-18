<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Companies;
use App\Models\Currencies;
use App\Models\customers;
use App\Models\operations;
use App\Models\Trips;
use App\Models\Trips_hotels;
use App\Models\User;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function storeBooking(Request $request): JsonResponse
    {
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

        $operation = DB::transaction(function () use ($data, $trip, $company, $currency, $hotel, $prices, $creator, $amount): operations {
            $customerData = $data['customer'];
            $customer = customers::query()->create([
                'name' => $customerData['name'],
                'email' => $customerData['email'] ?? null,
                'phone' => $customerData['phone'],
                'address' => $customerData['address'] ?? null,
            ]);

            return operations::query()->create([
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
                'remarks' => $data['remarks'] ?? null,
                'by_created' => $creator->id,
            ]);
        });

        return response()->json([
            'data' => [
                'booking_id' => $operation->booking_id,
                'status' => $operation->status,
                'amount' => $operation->amount_total,
                'currency' => $currency->code,
            ],
        ], 201);
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
