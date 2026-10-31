<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Companies;
use App\Models\Currencies;
use App\Models\customers;
use App\Models\Extras;
use App\Models\operations;
use App\Models\Ticket;
use App\Models\Trips;
use App\Services\StaffRegionAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use BaconQrCode\Renderer\Image\Svg;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffOperations extends Controller
{
public function showOperation(){
    $staff = Auth::guard('staff')->user();
    $trips = StaffRegionAccess::tripQuery($staff)->get();
return view('staff.Operations.dasboardOperations',compact('trips'));
}


public function store($trip)
{
    // Get trip
    $staff = Auth::guard('staff')->user();
    $regionIds = StaffRegionAccess::regionIds($staff);
    abort_if($regionIds === [], 403, 'لا توجد مناطق مرتبطة بحسابك. تواصل مع الإدارة.');

    $tripModel = StaffRegionAccess::tripQuery($staff)
        ->where('name', $trip)
        ->first();

  if (!$tripModel) { return back()->with('error', 'الرحلة غير موجودة.'); }
    $tripName = $tripModel->name;

    // Resolve the company's default booking entity from the signed-in staff member's tenant.
    $company = Companies::query()
        ->where('tenant_id', $staff->tenant_id)
        ->where('status', true)
        ->orderBy('id')
        ->first();

   if (!$company) { return back()->with('error', 'لا توجد شركة مسجلة لحساب شركتك.'); }

    // Get active hotels for this trip
$hotels = \App\Support\TenantDatabase::table('trips_hotels as th')
    ->join('hotels as h', 'th.hotel_id', '=', 'h.id')
    ->where('th.trip_id', $tripModel->id)
->where('th.status', true)
->where('h.status', true)
->where('h.region_id', $tripModel->region_id)
->select('th.id','h.name', 'th.pickup_time')
    ->get();
    $currencies=Currencies::all();
$companies = Companies::query()
    ->where('tenant_id', $staff->tenant_id)
    ->where('status', true)
    ->orderBy('name')
    ->get(['id', 'name']);
$prices_company = \App\Support\TenantDatabase::table('prices_company')
    ->select('price_adult', 'price_child', 'price_infant')
    ->where('id_company', $company->id)
    ->where('id_trip', $tripModel->id)
    ->where('id_currency', Currencies::query()->where('code', 'EGP')->value('id'))
    ->first();
if ($hotels->isEmpty()) { return back()->with('error', 'لا توجد فنادق متاحة لهذه الرحلة.'); }

    // Get active extras
    $exterTrips = Extras::where('status', 1)->get();

 if ($exterTrips->isEmpty()) { return back()->with('error', 'لا توجد خدمات إضافية متاحة.'); }


    $prefix = date('ym');


    $lastBooking = operations::where('booking_id', 'like', $prefix . '%')
        ->orderByDesc('booking_id')
        ->first();


    if ($lastBooking) {
        $lastNumber = (int) substr($lastBooking->booking_id, 4);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }

    // Example: 26090001
    $bookingId = $prefix . str_pad(
        $nextNumber,
        4,
        '0',
        STR_PAD_LEFT
    );

    // Current logged-in user
    $byCreated = $staff->id;

    // Create operation
    $operation = operations::create([
        'booking_id' => $bookingId,
        'id_trip'    => $tripModel->id,
        'id_company' => $company->id,
        'trip_date'  => today(),
        'trip_time'  => now()->format('H:i:s'),
        'by_created' => $byCreated,
    ]);

if (!$operation) { return back()->with('error', 'حدث خطأ أثناء إنشاء العملية.'); }

    // Store only the values needed after redirect
session([
    'tripName'   => $tripName,
    'bookingId'  => $bookingId,
    'hotels'     => $hotels,
    'exterTrips' => $exterTrips,
    'currencies'=>$currencies,
    'companies' => $companies,
    'company_id' => $company->id,
    'prices_company'=>$prices_company
]);


    return redirect()->route('created.operations');
}





public function showCreatedOperations(Request $request){
    $this->sessionOperation();
    return view('staff.Operations.createOperations');
}

public function cancelOperation()
{
    $operation = $this->sessionOperation();
    \App\Support\TenantDatabase::table('operation_extras')->where('operation_id', $operation->id)->delete();
    $operation->delete();

    session()->forget([
        'tripName',
        'bookingId',
        'hotels',
        'exterTrips',
        'currencies',
        'companies',
        'company_id',
        'prices_company',
        'currency_id',
        'currency_rate',
    ]);

    return redirect()
        ->route('operatoins.dashboard')
        ->with('success', 'تم إلغاء الحجز.');
}

public function updateCompanyPrices(Request $request)
{
    $validated = $request->validate([
        'company_id' => ['required', 'integer', 'tenant_exists:companies,id'],
    ]);

    $operation = $this->sessionOperation();
    $company = Companies::where('status', true)->findOrFail($validated['company_id']);
    $prices = \App\Support\TenantDatabase::table('prices_company')
        ->select('price_adult', 'price_child', 'price_infant')
        ->where('id_company', $company->id)
        ->where('id_trip', $operation->id_trip)
        ->where('id_currency', Currencies::query()->where('code', 'EGP')->value('id'))
        ->first();

    if (!$prices) {
        return response()->json([
            'message' => 'No prices are configured for this company and trip.',
        ], 422);
    }

    session([
        'company_id' => $company->id,
        'prices_company' => $prices,
    ]);

    return response()->json([
        'company_id' => $company->id,
        'prices' => [
            'adult' => (float) $prices->price_adult,
            'child' => (float) $prices->price_child,
            'infant' => (float) $prices->price_infant,
        ],
    ]);
}



public function storeCustomer(Request $request)
{
    try {

        // Validate customer data
        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'email' => 'nullable|email|max:255|unique:customers,email',

            'phone' => 'nullable|string|max:50|unique:customers,phone',

            'address' => 'nullable|string|max:255',

            'notes' => 'nullable|string',

        ], [

            'name.required' => 'Customer name is required.',

            'email.email' => 'Please enter a valid email address.',

            'email.unique' => 'This email already exists.',

            'phone.unique' => 'This phone number already exists.',

        ]);

    } catch (ValidationException $e) {

        // Return to the form with the entered data and errors
        return redirect()
            ->back()
            ->withInput()
            ->with('customer_form', true)
            ->withErrors($e->errors());
    }

    $operation = $this->sessionOperation();

    // Create customer
    $customer = customers::create([
        'name'    => $validated['name'],
        'email'   => $validated['email'] ?? null,
        'phone'   => $validated['phone'] ?? null,
        'address' => $validated['address'] ?? null,
        'notes'   => $validated['notes'] ?? null,
    ]);


    // Attach customer to operation
    $operation->update([
        'customer_id' => $customer->id
    ]);


    // Success
    return redirect()
        ->back()
        ->with('success', 'Customer added successfully.');

}
public function saveOperation(Request $request)
{
    try {

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'trip_date' => ['required', 'date'],
            'trip_time' => ['required'],

            'adults' => ['required', 'integer', 'min:0'],
            'children' => ['required', 'integer', 'min:0'],
            'info' => ['required', 'integer', 'min:0'],

            'extras' => ['nullable', 'array'],
            'extras.*' => ['integer', 'tenant_exists:extras,id'],

            'company_id' => [
                'required',
                'integer',
                'tenant_exists:companies,id'
            ],

            'hotel_id' => ['required', 'integer', 'tenant_exists:trips_hotels,id'],
            'pick_time' => ['nullable'],

            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id'
            ],

            'price' => ['required', 'numeric', 'min:0'],
            'extra' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'rate_tax' => ['required', 'numeric', 'min:0'],

            'method_payment' => [
                'required',
                'in:cash,credit'
            ],

            'paid' => [
                'nullable',
                'numeric',
                'min:0'
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */

        $ticket = DB::transaction(function () use ($request, $validated) {

            /*
            |--------------------------------------------------------------------------
            | Get Operation
            |--------------------------------------------------------------------------
            */

            $operation = $this->sessionOperation();

            if (! StaffRegionAccess::canAccessTripHotel(
                (int) $validated['hotel_id'],
                (int) $operation->id_trip,
                Auth::guard('staff')->user()
            )) {
                throw ValidationException::withMessages([
                    'hotel_id' => 'الفندق المحدد لا يتبع إحدى المناطق المصرح بها لحسابك.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Get Company
            |--------------------------------------------------------------------------
            */

            $company = Companies::where('status', true)
                ->findOrFail($validated['company_id']);


            /*
            |--------------------------------------------------------------------------
            | Get Company Prices
            |--------------------------------------------------------------------------
            */

            $pricesCompany = \App\Support\TenantDatabase::table('prices_company')
                ->where('id_company', $company->id)
                ->where('id_trip', $operation->id_trip)
                ->where('id_currency', Currencies::query()->where('code', 'EGP')->value('id'))
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */

            currency()->set($validated['currency_id']);

            $currency = currency()->selected();

            $currencyRate = currency()->rate();

            if ($currencyRate <= 0) {
                throw new \Exception('سعر العملة غير صحيح.');
            }


            /*
            |--------------------------------------------------------------------------
            | Base Prices
            |--------------------------------------------------------------------------
            |
            | الأسعار الموجودة في prices_company هي EGP
            |
            */

            $baseAdult = (float) ($pricesCompany->price_adult ?? 0);
            $baseChild = (float) ($pricesCompany->price_child ?? 0);
            $baseInfant = (float) ($pricesCompany->price_infant ?? 0);


            /*
            |--------------------------------------------------------------------------
            | Convert EGP -> Selected Currency
            |--------------------------------------------------------------------------
            */

            $priceAdult = currency()->convert($baseAdult);
            $priceChild = currency()->convert($baseChild);
            $priceInfant = currency()->convert($baseInfant);


            /*
            |--------------------------------------------------------------------------
            | Selected Extras
            |--------------------------------------------------------------------------
            */

            $selectedExtras = Extras::whereIn(
                'id',
                $validated['extras'] ?? []
            )
                ->where('status', true)
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Delete Old Extras
            |--------------------------------------------------------------------------
            */

            \App\Support\TenantDatabase::table('operation_extras')
                ->where('operation_id', $operation->id)
                ->delete();


            /*
            |--------------------------------------------------------------------------
            | Save Selected Extras
            |--------------------------------------------------------------------------
            */

            foreach ($selectedExtras as $extra) {

                $extraPrice = currency()->convert(
                    (float) $extra->price
                );

                \App\Support\TenantDatabase::table('operation_extras')->insert([
                    'operation_id' => $operation->id,
                    'extra_id' => $extra->id,
                    'price' => $extraPrice,
                    'quantity' => 1,
                    'amount_total' => $extraPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Save Session Data
            |--------------------------------------------------------------------------
            */

            session([
                'company_id' => $company->id,
                'prices_company' => $pricesCompany,
                'currency_id' => $currency->id,
                'currency_rate' => $currencyRate,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Calculate Subtotal
            |--------------------------------------------------------------------------
            */

            $subtotal = max(
                0,
                (float) $validated['price']
                + (float) $validated['extra']
                - (float) $validated['discount']
            );


            /*
            |--------------------------------------------------------------------------
            | Calculate Tax
            |--------------------------------------------------------------------------
            */

            $taxAmount = $subtotal
                * (float) $validated['rate_tax']
                / 100;


            /*
            |--------------------------------------------------------------------------
            | Calculate Total
            |--------------------------------------------------------------------------
            */

            $total = $subtotal + $taxAmount;


            /*
            |--------------------------------------------------------------------------
            | Keep Permanent EGP Total
            |--------------------------------------------------------------------------
            */

            $totalAmountEgp = round(
                $total * $currencyRate,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Calculate Paid
            |--------------------------------------------------------------------------
            */

            $paid = $validated['method_payment'] === 'cash'
                ? (float) ($validated['paid'] ?? 0)
                : 0;


            /*
            |--------------------------------------------------------------------------
            | Update Operation
            |--------------------------------------------------------------------------
            */

            $operation->update([

                'id_company' => $company->id,

                'status' => 'complete',

                'trip_date' => $validated['trip_date'],

                'trip_time' => $validated['trip_time'],

                'trip_hotel_id' => $validated['hotel_id'] ?? null,

                'id_currency' => $validated['currency_id'],

                'time_pickup' => $validated['pick_time'] ?? null,


                /*
                |--------------------------------------------------------------------------
                | Guests
                |--------------------------------------------------------------------------
                */

                'adults' => $validated['adults'],

                'children' => $validated['children'],

                'infants' => $validated['info'],


                /*
                |--------------------------------------------------------------------------
                | Prices
                |--------------------------------------------------------------------------
                */

                'price_adult' => $priceAdult,

                'price_child' => $priceChild,

                'price_infant' => $priceInfant,


                /*
                |--------------------------------------------------------------------------
                | Financial Data
                |--------------------------------------------------------------------------
                */

                'subtotal' => $validated['price'],

                'amount_extra' => $validated['extra'],

                'amount_discount' => $validated['discount'],

                'rate_tax' => $validated['rate_tax'],

                'amount_tax' => $taxAmount,

                'amount_total' => $total,

                /*
                | Permanent EGP amount
                */
                'totalamount' => $totalAmountEgp,

                'method_payment' => $validated['method_payment'],

                'amount_paid' => $paid,

                'amount_remaining' => max(
                    0,
                    $total - $paid
                ),


                /*
                |--------------------------------------------------------------------------
                | Payment Status
                |--------------------------------------------------------------------------
                */

                'status_payment' =>

                    $paid >= $total && $total > 0

                        ? 'paid'

                        : (
                            $paid > 0
                                ? 'partial'
                                : 'pending'
                        ),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Ticket
            |--------------------------------------------------------------------------
            */

            $ticket = Ticket::where(
                'operation_id',
                $operation->id
            )->first();


            if (!$ticket) {

                $expiresAt = Carbon::parse(
                    Carbon::parse(
                        $operation->trip_date
                    )->format('Y-m-d')
                    . ' '
                    . $operation->trip_time
                )->addHours(2);


                $ticket = Ticket::create([

                    'operation_id' => $operation->id,

                    'ticket_code' =>
                        'HST-'
                        . now()->format('Y')
                        . '-'
                        . str_pad(
                            $operation->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'token' => Str::random(40),

                    'status' => 'active',

                    'expires_at' => $expiresAt,
                ]);
            }


            return $ticket;
        });


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return redirect()->route('operation.ticket.download', $ticket);


    } catch (\Illuminate\Validation\ValidationException $e) {

        /*
        |--------------------------------------------------------------------------
        | Validation Error
        |--------------------------------------------------------------------------
        |
        | Laravel handles validation automatically.
        |
        */

        throw $e;


    } catch (\Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Log Error
        |--------------------------------------------------------------------------
        */

        \Illuminate\Support\Facades\Log::error('Save Operation Error', [

            'message' => $e->getMessage(),

            'file' => $e->getFile(),

            'line' => $e->getLine(),

            'request' => $request->except([
                '_token',
            ]),

            'booking_id' => session('bookingId'),

        ]);


        /*
        |--------------------------------------------------------------------------
        | Return Back With Request Data
        |--------------------------------------------------------------------------
        */

        $message = app()->environment('local')
            ? $e->getMessage()
            : 'حدث خطأ أثناء حفظ الحجز، برجاء المحاولة مرة أخرى.';


        return redirect()
            ->back()
            ->withInput()
            ->with('error', $message);
    }
}

public function ticketDownloadPage(Ticket $ticket)
{
    return view('staff.Operations.ticket-download', [
        'ticket' => $ticket,
        'pdfUrl' => route('operation.ticket.pdf', $ticket),
    ]);
}

public function ticketPdf(Ticket $ticket)
{
    $ticket->load('operation');
    $linkUrl = route('operation.ticket', $ticket->id);
    $render = new Svg();
    $render->setWidth(200);
    $render->setHeight(200);
    $qrCode = base64_encode((new Writer($render))->writeString($linkUrl));
    $pdf = Pdf::loadView('staff.Operations.ticket-pdf', compact('qrCode', 'ticket'));

    return $pdf->download($ticket->ticket_code.'.pdf');
}

public function showTicket(Ticket $ticket)
{
    $ticket->load('operation');

    if (
        $ticket->status == 'active' &&
        $ticket->expires_at &&
        now()->greaterThan($ticket->expires_at)
    ) {
        $ticket->update([
            'status' => 'expired'
        ]);
    }

    $renderer = new Svg();

    $renderer->setWidth(200);
    $renderer->setHeight(200);

    $qrCode = base64_encode(
        (new Writer($renderer))->writeString(
            route('operation.ticket', $ticket)
        )
    );

    return view(
        'staff.Operations.ticket',
        compact('ticket', 'qrCode')
    );
}

private function sessionOperation(): operations
{
    return operations::query()
        ->where('booking_id', session('bookingId'))
        ->where('by_created', Auth::guard('staff')->id())
        ->firstOrFail();
}


}
