<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Boat_Driver;
use App\Models\Companies;
use App\Models\Currencies;
use App\Models\Drive;
use App\Models\Extras;
use App\Models\Hotel;
use App\Models\Supplier;
use App\Models\Trips;
use App\Services\TripImageStorage;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\OperationRevenueAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperationResourcesController extends Controller
{
    public function companies()
    {
        $companies = Companies::query()
            ->when(! request()->boolean('include_archived'), fn ($query) => $query->where('status', true))
            ->orderBy('name')
            ->get();

        return view('admin.pages.companies', $this->pageData([
            'companies' => $companies,
        ]));
    }

    public function storeCompany(Request $request)
    {
        $company = Companies::query()->create($this->validateCompany($request));
        $this->audit('إضافة شركة', "{$company->name} (#{$company->id})");

        return redirect()->route('accounting.companies')->with('status', 'تمت إضافة الشركة بنجاح.');
    }

    public function updateCompany(Request $request, Companies $company)
    {
        $company->update($this->validateCompany($request, $company));
        $this->audit('تعديل شركة', "{$company->name} (#{$company->id})");

        return $this->redirectToResourcePage($request, 'accounting.companies', 'تم تحديث بيانات الشركة.');
    }

    public function archiveCompany(Companies $company)
    {
        $company->update(['status' => false]);
        $this->audit('أرشفة شركة', "{$company->name} (#{$company->id})");

        return redirect()->route('accounting.companies')->with('status', 'تم إيقاف الشركة مع الاحتفاظ بالحجوزات والأسعار السابقة.');
    }

    private function validateCompany(Request $request, ?Companies $company = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'name')
                    ->ignore($company?->id)
                    ->where('tenant_id', \App\Support\TenantContext::id()),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => [$company ? 'required' : 'sometimes', 'boolean'],
        ]) + ['status' => $request->boolean('status', true)];
    }

    public function extras()
    {
        $extras = Extras::query()
            ->when(! request()->boolean('include_archived'), fn ($query) => $query->where('status', true))
            ->orderBy('name')
            ->get();

        return view('admin.pages.extras', $this->pageData([
            'extras' => $extras,
        ]));
    }

    public function storeExtra(Request $request)
    {
        $extra = Extras::query()->create($this->validateExtra($request));
        $this->audit('إضافة خدمة إضافية', "{$extra->name} (#{$extra->id})");

        return redirect()->route('accounting.extras')->with('status', 'تمت إضافة الخدمة الإضافية بنجاح.');
    }

    public function updateExtra(Request $request, Extras $extra)
    {
        $extra->update($this->validateExtra($request, $extra));
        $this->audit('تعديل خدمة إضافية', "{$extra->name} (#{$extra->id})");

        return $this->redirectToResourcePage($request, 'accounting.extras', 'تم تحديث بيانات الخدمة الإضافية.');
    }

    public function archiveExtra(Extras $extra)
    {
        $extra->update(['status' => false]);
        $this->audit('أرشفة خدمة إضافية', "{$extra->name} (#{$extra->id})");

        return redirect()->route('accounting.extras')->with('status', 'تم إيقاف الخدمة مع الاحتفاظ بسجلاتها السابقة.');
    }

    private function validateExtra(Request $request, ?Extras $extra = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('extras', 'name')
                    ->ignore($extra?->id)
                    ->where('tenant_id', \App\Support\TenantContext::id()),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'rate_tax' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => [$extra ? 'required' : 'sometimes', 'boolean'],
        ]) + ['status' => $request->boolean('status', true)];
    }

    public function companyPrices()
    {
        $currency = Currencies::query()->where('code', 'EGP')->firstOrFail();
        $companies = Companies::query()->where('status', true)->orderBy('name')->get(['id', 'name']);
        $trips = Trips::query()->where('status', true)->orderBy('name')->get(['id', 'name']);
        $companyNames = Companies::query()->pluck('name', 'id');
        $tripNames = Trips::query()->pluck('name', 'id');
        $prices = \App\Support\TenantDatabase::table('prices_company')
            ->where('id_currency', $currency->id)
            ->orderBy('id_company')
            ->orderBy('id_trip')
            ->get()
            ->map(function ($price) use ($companyNames, $tripNames) {
                $price->company_name = $companyNames->get($price->id_company, 'شركة غير نشطة');
                $price->trip_name = $tripNames->get($price->id_trip, 'رحلة غير نشطة');

                return $price;
            });

        return view('admin.pages.company_prices', $this->pageData([
            'companies' => $companies,
            'trips' => $trips,
            'prices' => $prices,
            'currency' => $currency,
        ]));
    }

    public function drivers()
    {
        $drivers = Drive::query()
            ->with(['vehicleDrivers.vehicle'])
            ->orderBy('name')
            ->get();

        return view('admin.pages.drivers', $this->pageData([
            'drivers' => $drivers,
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function boatDrivers()
    {
        $boatDrivers = Boat_Driver::query()
            ->with('boat')
            ->orderBy('name')
            ->get();

        return view('admin.pages.boat_drivers', $this->pageData([
            'boatDrivers' => $boatDrivers,
            'boats' => Boat::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function storeDriver(Request $request)
    {
        $data = $this->validateDriver($request);
        $vehicleIds = $data['vehicle_ids'] ?? [];
        unset($data['vehicle_ids']);

        DB::transaction(function () use ($data, $vehicleIds): void {
            $driver = Drive::query()->create($data + ['status' => $data['status'] ?? 'available']);
            $this->syncDriverVehicles((int) $driver->id, $vehicleIds);
        });

        $this->audit('إضافة سائق سيارة أو أتوبيس', $data['name']);

        return redirect()->route('accounting.drivers')->with('status', 'تمت إضافة السائق بنجاح.');
    }

    public function updateDriver(Request $request, Drive $driver)
    {
        $data = $this->validateDriver($request);
        $vehicleIds = $data['vehicle_ids'] ?? [];
        unset($data['vehicle_ids']);

        DB::transaction(function () use ($driver, $data, $vehicleIds): void {
            $driver->update($data);
            $this->syncDriverVehicles((int) $driver->id, $vehicleIds);
        });

        $this->audit('تعديل سائق سيارة أو أتوبيس', "{$driver->name} (#{$driver->id})");

        return redirect()->route('accounting.drivers')->with('status', 'تم تحديث بيانات السائق.');
    }

    public function archiveDriver(Drive $driver)
    {
        DB::transaction(function () use ($driver): void {
            \App\Support\TenantDatabase::table('vehicle_drivers')->where('driver_id', $driver->id)->delete();
            $driver->update(['status' => 'inactive']);
        });

        $this->audit('أرشفة سائق سيارة أو أتوبيس', "{$driver->name} (#{$driver->id})");

        return redirect()->route('accounting.drivers')->with('status', 'تم إيقاف السائق وفك ربطه بالسيارات والأتوبيسات.');
    }

    public function restoreDriver(int $driver)
    {
        $record = Drive::query()->findOrFail($driver);
        $record->update(['status' => 'available']);
        $this->audit('إعادة تفعيل سائق سيارة أو أتوبيس', "{$record->name} (#{$record->id})");

        return redirect()->route('accounting.drivers')->with('status', 'تمت إعادة تفعيل السائق. اربطه بالمركبة من صفحة التعديل.');
    }

    public function storeBoatDriver(Request $request)
    {
        $data = $this->validateBoatDriver($request);
        $driver = Boat_Driver::query()->create($data);
        $this->audit('إضافة سائق مركب', "{$driver->name} — المركب #{$driver->boat_id}");

        return redirect()->route('accounting.boat-drivers')->with('status', 'تمت إضافة سائق المركب بنجاح.');
    }

    public function updateBoatDriver(Request $request, int $driver)
    {
        $boatDriver = Boat_Driver::query()->findOrFail($driver);
        $boatDriver->update($this->validateBoatDriver($request));
        $this->audit('تعديل سائق مركب', "{$boatDriver->name} (#{$boatDriver->id})");

        return redirect()->route('accounting.boat-drivers')->with('status', 'تم تحديث بيانات سائق المركب.');
    }

    public function archiveBoatDriver(int $driver)
    {
        $boatDriver = Boat_Driver::query()->findOrFail($driver);
        $boatDriver->update(['is_active' => false]);
        $this->audit('إيقاف سائق مركب', "{$boatDriver->name} (#{$boatDriver->id})");

        return redirect()->route('accounting.boat-drivers')->with('status', 'تم إيقاف سائق المركب.');
    }

    public function restoreBoatDriver(int $driver)
    {
        $record = Boat_Driver::query()->findOrFail($driver);
        $record->update(['is_active' => true]);
        $this->audit('إعادة تفعيل سائق مركب', "{$record->name} (#{$record->id})");

        return redirect()->route('accounting.boat-drivers')->with('status', 'تمت إعادة تفعيل سائق المركب.');
    }

    private function validateDriver(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'LicenseDate' => ['nullable', 'date'],
            'degreeOfLeave' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:20'],
            'vehicle_ids' => ['sometimes', 'array'],
            'vehicle_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('vehicles', 'id')
                    ->where('tenant_id', \App\Support\TenantContext::id())
                    ->where('status', 'active'),
            ],
        ]);
    }

    private function validateBoatDriver(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'boat_id' => [
                'required',
                'integer',
                Rule::exists('boats', 'id')
                    ->where('tenant_id', \App\Support\TenantContext::id())
                    ->where('is_active', true),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    private function syncDriverVehicles(int $driverId, array $vehicleIds): void
    {
        \App\Support\TenantDatabase::table('vehicle_drivers')->where('driver_id', $driverId)->delete();
        if ($vehicleIds === []) {
            return;
        }

        $now = now();
        \App\Support\TenantDatabase::table('vehicle_drivers')->insert(array_map(
            static fn (int $vehicleId): array => [
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
                'assignment_role' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_map('intval', $vehicleIds),
        ));
    }

    public function saveCompanyPrice(Request $request)
    {
        $tenantId = \App\Support\TenantContext::id();
        $currency = Currencies::query()->where('code', 'EGP')->firstOrFail();
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->where('tenant_id', $tenantId)->where('status', true)],
            'trip_id' => ['required', 'integer', Rule::exists('trips', 'id')->where('tenant_id', $tenantId)->where('status', true)],
            'price_adult' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'price_child' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'price_infant' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        \App\Support\TenantDatabase::table('prices_company')->updateOrInsert(
            [
                'id_company' => $data['company_id'],
                'id_trip' => $data['trip_id'],
                'id_currency' => $currency->id,
            ],
            [
                'price_adult' => $data['price_adult'],
                'price_child' => $data['price_child'],
                'price_infant' => $data['price_infant'],
            ],
        );

        $this->audit(
            'تحديث أسعار رحلة لشركة',
            "الشركة #{$data['company_id']} — الرحلة #{$data['trip_id']} — {$currency->code}",
        );

        return redirect()->route('accounting.company-prices')->with('status', 'تم حفظ الأسعار بالجنيه المصري.');
    }

    public function boats()
    {
        $boats = Boat::query()->with(['supplier', 'revenueAccount', 'region'])->orderBy('name');
        if (! request()->boolean('include_archived')) {
            $boats->where('is_active', true);
        }

        return view('admin.pages.boats', $this->pageData([
            'boats' => $boats->get(),
            'suppliers' => Supplier::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'regions' => \App\Support\TenantDatabase::table('regions')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function vehicles()
    {
        $vehicles = Vehicle::query()->with(['vehicleType', 'revenueAccount', 'region', 'supplier'])->orderBy('name');
        if (! request()->boolean('include_archived')) {
            $vehicles->where('status', 'active');
        }

        return view('admin.pages.vehicles', $this->pageData([
            'vehicles' => $vehicles->get(),
            'vehicleTypes' => VehicleType::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'regions' => \App\Support\TenantDatabase::table('regions')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function trips()
    {
        $includeArchived = request()->boolean('include_archived');
        $trips = Trips::query()
            ->with(['region', 'images'])
            ->withCount(['tripHotels as active_hotels_count' => fn ($query) => $query->where('status', true)])
            ->when(! $includeArchived, fn ($query) => $query->where('status', true))
            ->orderBy('name')
            ->get();

        return view('admin.pages.trips', $this->pageData([
            'trips' => $trips,
            'regions' => \App\Support\TenantDatabase::table('regions')->orderBy('name')->get(['id', 'name', 'is_active']),
        ]));
    }

    public function hotels()
    {
        $includeArchived = request()->boolean('include_archived');
        $hotels = Hotel::query()
            ->with('region')
            ->withCount('tripHotels')
            ->when(! $includeArchived, fn ($query) => $query->where('status', true))
            ->orderBy('name')
            ->get();

        return view('admin.pages.hotels', $this->pageData([
            'hotels' => $hotels,
            'regions' => \App\Support\TenantDatabase::table('regions')->orderBy('name')->get(['id', 'name', 'is_active']),
        ]));
    }

    public function tripsAndHotels()
    {
        $route = request()->query('tab') === 'hotels' ? 'accounting.hotels' : 'accounting.trips';
        $query = request()->boolean('include_archived') ? ['include_archived' => 1] : [];

        return redirect()->route($route, $query);
    }

    public function storeTrip(Request $request, TripImageStorage $imageStorage)
    {
        $data = $this->validateTrip($request);
        $images = $data['images'] ?? [];
        $primaryImage = $data['primary_image'] ?? null;
        unset($data['images'], $data['remove_images'], $data['replace_images'], $data['primary_image']);
        $createdPaths = [];

        try {
            $trip = DB::transaction(function () use ($data, $images, $primaryImage, $imageStorage, &$createdPaths): Trips {
                $trip = Trips::query()->create($data);
                $this->syncTripImages($trip, $images, [], [], $primaryImage, $imageStorage, $createdPaths);

                return $trip;
            });
        } catch (\Throwable $exception) {
            try {
                $this->removeStoredImages($createdPaths);
            } catch (\Throwable $cleanupException) {
                report($cleanupException);
            }
            throw $exception;
        }

        $this->audit('إضافة رحلة', "{$trip->name} (#{$trip->id})");

        return redirect()->route('accounting.trips')->with('status', 'تمت إضافة الرحلة بنجاح.');
    }

    public function updateTrip(Request $request, Trips $trip, TripImageStorage $imageStorage)
    {
        $data = $this->validateTrip($request, $trip);
        $images = $data['images'] ?? [];
        $removeIds = $data['remove_images'] ?? [];
        $replaceImages = $data['replace_images'] ?? [];
        $primaryImage = $data['primary_image'] ?? null;
        unset($data['images'], $data['remove_images'], $data['replace_images'], $data['primary_image']);
        $createdPaths = [];
        $removedPaths = [];

        try {
            DB::transaction(function () use ($trip, $data, $images, $removeIds, $replaceImages, $primaryImage, $imageStorage, &$createdPaths, &$removedPaths): void {
                $trip->update($data);
                $this->syncTripImages($trip, $images, $removeIds, $replaceImages, $primaryImage, $imageStorage, $createdPaths, $removedPaths);
            });
        } catch (\Throwable $exception) {
            try {
                $this->removeStoredImages($createdPaths);
            } catch (\Throwable $cleanupException) {
                report($cleanupException);
            }
            throw $exception;
        }

        $this->removeStoredImages($removedPaths);
        $this->audit('تعديل رحلة', "{$trip->name} (#{$trip->id})");

        return $this->redirectToResourcePage($request, 'accounting.trips', 'تم تحديث بيانات الرحلة.');
    }

    public function archiveTrip(Trips $trip)
    {
        $trip->update(['status' => false]);
        $this->audit('أرشفة رحلة', "{$trip->name} (#{$trip->id})");

        return redirect()->route('accounting.trips')->with('status', 'تم أرشفة الرحلة مع الاحتفاظ بالحجوزات والسجلات السابقة.');
    }

    public function storeHotel(Request $request)
    {
        $data = $this->validateHotel($request);
        $hotel = Hotel::query()->create($data);
        $this->audit('إضافة فندق', "{$hotel->name} (#{$hotel->id})");

        return redirect()->route('accounting.hotels')->with('status', 'تمت إضافة الفندق بنجاح.');
    }

    public function updateHotel(Request $request, Hotel $hotel)
    {
        $hotel->update($this->validateHotel($request, $hotel));
        $this->audit('تعديل فندق', "{$hotel->name} (#{$hotel->id})");

        return $this->redirectToResourcePage($request, 'accounting.hotels', 'تم تحديث بيانات الفندق.');
    }

    public function archiveHotel(Hotel $hotel)
    {
        $hotel->update(['status' => false]);
        $this->audit('أرشفة فندق', "{$hotel->name} (#{$hotel->id})");

        return redirect()->route('accounting.hotels')->with('status', 'تم أرشفة الفندق مع الاحتفاظ بالحجوزات والسجلات السابقة.');
    }

    private function validateTrip(Request $request, ?Trips $trip = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('trips', 'name')
                    ->ignore($trip?->id)
                    ->where('tenant_id', \App\Support\TenantContext::id()),
            ],
            'region_id' => [
                'nullable',
                'integer',
                Rule::exists('regions', 'id')->where('tenant_id', \App\Support\TenantContext::id())
                    ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $trip?->region_id ?? 0)),
            ],
            'time_trip' => ['nullable', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => [$trip ? 'required' : 'sometimes', 'boolean'],
            'includes_items' => ['nullable', 'array', 'max:100'],
            'includes_items.*' => ['nullable', 'string', 'max:500'],
            'excludes_items' => ['nullable', 'array', 'max:100'],
            'excludes_items.*' => ['nullable', 'string', 'max:500'],
            'faq_items' => ['nullable', 'array', 'max:50'],
            'faq_items.*' => ['array'],
            'faq_items.*.question' => ['nullable', 'string', 'max:1000'],
            'faq_items.*.answer' => ['nullable', 'string', 'max:2000'],
            'includes_text' => ['nullable', 'string', 'max:8000'],
            'excludes_text' => ['nullable', 'string', 'max:8000'],
            'faq_questions' => ['nullable', 'string', 'max:8000'],
            'faq_answers' => ['nullable', 'string', 'max:8000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:8192'],
            'primary_image' => ['nullable', 'string', 'max:40', 'regex:/^(existing:\d+|new:\d+)$/'],
            'replace_images' => ['nullable', 'array', 'max:10'],
            'replace_images.*' => ['image', 'mimes:jpeg,png,webp', 'max:8192'],
            'remove_images' => ['nullable', 'array', 'max:10'],
            'remove_images.*' => [
                'integer',
                Rule::exists('trip_images', 'id')->where('tenant_id', \App\Support\TenantContext::id()),
            ],
        ]) + ['status' => $request->boolean('status', true)];

        $includes = isset($data['includes_items'])
            ? $this->cleanTripItems($data['includes_items'])
            : $this->splitTripLines($data['includes_text'] ?? '');
        $excludes = isset($data['excludes_items'])
            ? $this->cleanTripItems($data['excludes_items'])
            : $this->splitTripLines($data['excludes_text'] ?? '');
        $faqs = [];
        if (isset($data['faq_items'])) {
            foreach ($data['faq_items'] as $faq) {
                $question = trim($faq['question'] ?? '');
                $answer = trim($faq['answer'] ?? '');
                if ($question === '' && $answer === '') {
                    continue;
                }
                if ($question === '' || $answer === '') {
                    throw ValidationException::withMessages([
                        'faq_items' => 'يجب إدخال السؤال والإجابة معًا لكل سؤال.',
                    ]);
                }
                $faqs[] = ['question' => $question, 'answer' => $answer];
            }
        } else {
            $questions = $this->splitTripLines($data['faq_questions'] ?? '');
            $answers = $this->splitTripLines($data['faq_answers'] ?? '');
            if (count($questions) !== count($answers)) {
                throw ValidationException::withMessages([
                    'faq_answers' => 'يجب إدخال إجابة لكل سؤال وبنفس الترتيب.',
                ]);
            }
            $faqs = array_map(
                fn (string $question, string $answer): array => ['question' => $question, 'answer' => $answer],
                $questions,
                $answers,
            );
        }

        $data['includes'] = $includes;
        $data['excludes'] = $excludes;
        $data['faqs'] = $faqs;
        unset(
            $data['includes_items'],
            $data['excludes_items'],
            $data['faq_items'],
            $data['includes_text'],
            $data['excludes_text'],
            $data['faq_questions'],
            $data['faq_answers'],
        );

        return $data;
    }

    private function splitTripLines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $text) ?: []),
            fn (string $line): bool => $line !== '',
        ));
    }

    private function cleanTripItems(array $items): array
    {
        return array_values(array_filter(
            array_map(static fn ($item): string => trim((string) $item), $items),
            static fn (string $item): bool => $item !== '',
        ));
    }

    private function syncTripImages(
        Trips $trip,
        array $uploads,
        array $removeIds,
        array $replacements,
        ?string $primaryImage,
        TripImageStorage $imageStorage,
        array &$createdPaths,
        array &$removedPaths = [],
    ): void {
        $replaceIds = array_map('intval', array_keys($replacements));
        if (array_intersect(array_map('intval', $removeIds), $replaceIds) !== []) {
            throw ValidationException::withMessages([
                'replace_images' => 'لا يمكن حذف الصورة واستبدالها في نفس الوقت.',
            ]);
        }

        $toRemove = $trip->images()->whereIn('id', $removeIds)->get();
        $toReplace = $trip->images()->whereIn('id', $replaceIds)->get();
        if (
            $toRemove->count() !== count(array_unique($removeIds))
            || $toReplace->count() !== count(array_unique($replaceIds))
        ) {
            throw ValidationException::withMessages([
                'remove_images' => 'بعض الصور المحددة لا تخص هذه الرحلة.',
            ]);
        }

        if ($trip->images()->count() - $toRemove->count() + count($uploads) > 10) {
            throw ValidationException::withMessages([
                'images' => 'الحد الأقصى هو 10 صور لكل رحلة.',
            ]);
        }

        foreach ($toRemove as $image) {
            $removedPaths[] = $image->path;
            $image->delete();
        }

        foreach ($toReplace as $image) {
            $upload = $replacements[$image->id] ?? $replacements[(string) $image->id];
            $path = $imageStorage->store($upload, (int) \App\Support\TenantContext::id());
            $createdPaths[] = $path;
            $removedPaths[] = $image->path;
            $image->update(['path' => $path]);
        }

        $sortOrder = (int) ($trip->images()->max('sort_order') ?? -1) + 1;
        $newImages = [];
        foreach (array_values($uploads) as $index => $upload) {
            $path = $imageStorage->store($upload, (int) \App\Support\TenantContext::id());
            $createdPaths[] = $path;
            $newImages[$index] = $trip->images()->create(['path' => $path, 'sort_order' => $sortOrder++]);
        }

        $images = $trip->images()->orderBy('sort_order')->get();
        if ($primaryImage !== null) {
            [$type, $value] = explode(':', $primaryImage, 2);
            $primary = $type === 'existing'
                ? $images->firstWhere('id', (int) $value)
                : ($newImages[(int) $value] ?? null);

            if (! $primary) {
                throw ValidationException::withMessages([
                    'primary_image' => 'الصورة الرئيسية المختارة غير موجودة ضمن صور الرحلة.',
                ]);
            }

            $images = $images->reject(fn ($image): bool => $image->is($primary))
                ->prepend($primary)
                ->values();
        }

        foreach ($images as $index => $image) {
            if ((int) $image->sort_order !== $index) {
                $image->update(['sort_order' => $index]);
            }
        }
    }

    private function removeStoredImages(array $paths): void
    {
        if ($paths !== [] && ! Storage::disk('public')->delete($paths)) {
            throw new \RuntimeException('تعذر حذف صور الرحلة من التخزين.');
        }
    }

    private function validateHotel(Request $request, ?Hotel $hotel = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hotels', 'name')
                    ->ignore($hotel?->id)
                    ->where('tenant_id', \App\Support\TenantContext::id()),
            ],
            'region_id' => [
                'nullable',
                'integer',
                Rule::exists('regions', 'id')->where('tenant_id', \App\Support\TenantContext::id())
                    ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $hotel?->region_id ?? 0)),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => [$hotel ? 'required' : 'sometimes', 'boolean'],
        ]) + ['status' => $request->boolean('status', true)];
    }

    private function redirectToResourcePage(Request $request, string $routeName, string $message)
    {
        $query = $request->boolean('_include_archived') ? ['include_archived' => 1] : [];

        return redirect()->route($routeName, $query)->with('status', $message);
    }

    public function suppliers()
    {
        $suppliers = Supplier::query()
            ->withCount('boats')
            ->when(! request()->boolean('include_archived'), fn ($query) => $query->where('status', true))
            ->orderBy('name')
            ->get();

        return view('admin.pages.suppliers', $this->pageData([
            'suppliers' => $suppliers,
        ]));
    }

    public function storeSupplier(Request $request)
    {
        $data = $this->validateSupplier($request);
        $supplier = Supplier::query()->create($data);
        $this->audit('إضافة مورد', "{$supplier->name} (#{$supplier->id})");

        return redirect()->route('accounting.suppliers')->with('status', 'تمت إضافة المورد بنجاح.');
    }

    public function updateSupplier(Request $request, Supplier $supplier)
    {
        $data = $this->validateSupplier($request, $supplier);
        $supplier->update($data);
        $this->audit('تعديل مورد', "{$supplier->name} (#{$supplier->id})");

        return redirect()->route('accounting.suppliers', $request->boolean('_include_archived') ? ['include_archived' => 1] : [])
            ->with('status', 'تم تحديث بيانات المورد.');
    }

    public function archiveSupplier(Supplier $supplier)
    {
        $supplier->update(['status' => false]);
        $this->audit('أرشفة مورد', "{$supplier->name} (#{$supplier->id})");

        return redirect()->route('accounting.suppliers')->with('status', 'تم أرشفة المورد، مع الاحتفاظ بالارتباطات والسجلات السابقة.');
    }

    private function validateSupplier(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')
                    ->ignore($supplier?->id)
                    ->where('tenant_id', \App\Support\TenantContext::id()),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => [$supplier ? 'required' : 'sometimes', 'boolean'],
        ]) + ['status' => $request->boolean('status', true)];
    }

    public function storeBoat(Request $request, OperationRevenueAccountService $revenueAccounts)
    {
        $data = $this->validateBoat($request);

        if ($data['ownership_type'] === 'internal') {
            $data['supplier_id'] = null;
            $data['supplier_price'] = null;
        }

        DB::transaction(function () use ($data, $revenueAccounts): void {
            $boat = Boat::query()->create($data + ['is_active' => true]);
            $revenueAccounts->ensure('boat', (int) $boat->id, $boat->name, $boat->ownership_type);
            $this->audit('إضافة مركب', "{$boat->name} (#{$boat->id}) — {$boat->ownership_type}");
        });

        return redirect()->route('accounting.boats')->with('status', 'تمت إضافة المركب وحساب إيراده في شجرة الحسابات.');
    }

    public function updateBoat(Request $request, Boat $boat, OperationRevenueAccountService $revenueAccounts)
    {
        $data = $this->validateBoat($request, $boat);

        if ($data['ownership_type'] === 'internal') {
            $data['supplier_id'] = null;
            $data['supplier_price'] = null;
        }

        DB::transaction(function () use ($data, $boat, $revenueAccounts): void {
            $boat->update($data);
            $revenueAccounts->ensure('boat', (int) $boat->id, $boat->name, $boat->ownership_type);
            $this->audit('تعديل مركب', "{$boat->name} (#{$boat->id}) — {$boat->ownership_type}");
        });

        return redirect()->route('accounting.boats')->with('status', 'تم تحديث بيانات المركب وحساب إيراده.');
    }

    public function deleteBoat(Boat $boat)
    {
        DB::transaction(function () use ($boat): void {
            $boat->update(['is_active' => false]);
            $this->audit('أرشفة مركب', "{$boat->name} (#{$boat->id})");
        });

        return redirect()->route('accounting.boats')->with('status', 'تم حذف المركب من الموارد النشطة مع الاحتفاظ بسجلاته التاريخية.');
    }

    public function storeVehicle(Request $request, OperationRevenueAccountService $revenueAccounts)
    {
        $data = $this->validateVehicle($request);

        if ($data['ownership_type'] === 'internal') {
            $data['supplier_id'] = null;
            $data['supplier_price'] = null;
        }

        DB::transaction(function () use ($data, $revenueAccounts): void {
            $vehicle = Vehicle::query()->create($data + ['created_at' => now()]);
            $revenueAccounts->ensure('vehicle', (int) $vehicle->id, $vehicle->name, $vehicle->ownership_type);
            $this->audit('إضافة سيارة', "{$vehicle->name} (#{$vehicle->id}) — {$vehicle->ownership_type}");
        });

        return redirect()->route('accounting.vehicles')->with('status', 'تمت إضافة السيارة وحساب إيرادها في شجرة الحسابات.');
    }

    public function updateVehicle(Request $request, Vehicle $vehicle, OperationRevenueAccountService $revenueAccounts)
    {
        $data = $this->validateVehicle($request, $vehicle);

        if ($data['ownership_type'] === 'internal') {
            $data['supplier_id'] = null;
            $data['supplier_price'] = null;
        }

        DB::transaction(function () use ($data, $vehicle, $revenueAccounts): void {
            $vehicle->update($data);
            $revenueAccounts->ensure('vehicle', (int) $vehicle->id, $vehicle->name, $vehicle->ownership_type);
            $this->audit('تعديل سيارة', "{$vehicle->name} (#{$vehicle->id}) — {$vehicle->ownership_type}");
        });

        return redirect()->route('accounting.vehicles')->with('status', 'تم تحديث بيانات السيارة وحساب إيرادها.');
    }

    public function deleteVehicle(Vehicle $vehicle)
    {
        DB::transaction(function () use ($vehicle): void {
            $vehicle->update(['status' => 'inactive']);
            $this->audit('أرشفة سيارة', "{$vehicle->name} (#{$vehicle->id})");
        });

        return redirect()->route('accounting.vehicles')->with('status', 'تم حذف السيارة من الموارد النشطة مع الاحتفاظ بسجلاتها التاريخية.');
    }

    private function validateBoat(Request $request, ?Boat $boat = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('boats', 'code')->ignore($boat?->id)->where('tenant_id', \App\Support\TenantContext::id())],
            'ownership_type' => ['required', Rule::in(['internal', 'external'])],
            'supplier_id' => ['nullable', 'required_if:ownership_type,external', 'integer', Rule::exists('suppliers', 'id')->where('status', true)->where('tenant_id', \App\Support\TenantContext::id())],
            'supplier_price' => ['nullable', 'required_if:ownership_type,external', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1'],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')->where('is_active', true)->where('tenant_id', \App\Support\TenantContext::id())],
            'type' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:100', Rule::unique('boats', 'registration_number')->ignore($boat?->id)->where('tenant_id', \App\Support\TenantContext::id())],
            'is_active' => [$boat ? 'required' : 'sometimes', 'boolean'],
        ]);
    }

    private function validateVehicle(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'code')->ignore($vehicle?->id)->where('tenant_id', \App\Support\TenantContext::id())],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')->where('is_active', true)->where('tenant_id', \App\Support\TenantContext::id())],
            'ownership_type' => ['required', Rule::in(['internal', 'external'])],
            'supplier_id' => ['nullable', 'required_if:ownership_type,external', 'integer', Rule::exists('suppliers', 'id')->where('status', true)->where('tenant_id', \App\Support\TenantContext::id())],
            'supplier_price' => ['nullable', 'required_if:ownership_type,external', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'codes' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'codee' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function pageData(array $data): array
    {
        $user = auth()->user();
        $settings = \App\Support\TenantDatabase::table('settings')->pluck('value', 'key');

        return $data + [
            'user' => $user,
            'companyName' => $settings->get('company_name', 'النظام المحاسبي المتكامل'),
            'roleName' => 'مدير النظام',
        ];
    }

    private function audit(string $action, string $details): void
    {
        \App\Support\TenantDatabase::table('audit_log')->insert([
            'user' => auth()->user()->name,
            'action' => $action,
            'details' => $details,
            'at' => now(),
        ]);
    }
}
