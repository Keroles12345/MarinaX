<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\OperationResourcesController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Staff\StaffOperations;
use App\Http\Controllers\DailyOpsController;
use App\Http\Controllers\OperationAssignmentController;
use App\Http\Controllers\EmployeeCustodyController;
use App\Http\Controllers\BoatRevenueController;
use App\Http\Controllers\VehicleRevenueController;
use App\Http\Middleware\StaffOperationsMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\SuperAdmin\TenantController;
Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $data = $request->validate(['locale' => ['required', 'in:ar,en']]);
    $request->session()->put('locale', $data['locale']);

    return back();
})->name('locale.update');


Route::get('/dashboard', [StaffAuthController::class, 'showLogin'])
    ->name('staff.login');
    Route::get('/admin', [AdminController::class, 'showLogin'])
    ->name('Admin.login');

    Route::get('/superadmin', [TenantController::class, 'loginForm'])->name('superadmin.login');
    Route::post('/superadmin/login', [TenantController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('superadmin.login.handle');

    Route::prefix('superadmin')->name('superadmin.')->middleware('superadmin')->group(function () {
        Route::get('/dashboard', [TenantController::class, 'dashboard'])->name('dashboard');
        Route::get('/companies', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/companies/create', [TenantController::class, 'createTenantPage'])->name('tenants.create');
        Route::get('/users', [TenantController::class, 'usersIndex'])->name('users.index');
        Route::get('/users/create', [TenantController::class, 'createUserPage'])->name('users.create');
        Route::post('/logout', [TenantController::class, 'logout'])->name('logout');
        Route::post('/companies', [TenantController::class, 'store'])->name('tenants.store');
        Route::put('/companies/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('/users', [TenantController::class, 'createUser'])->name('users.store');
        Route::put('/users/{userId}/company', [TenantController::class, 'assignUser'])->name('users.assign-company');
        Route::put('/users/{userId}', [TenantController::class, 'updateUser'])->name('users.update');
    });


Route::post('/staff/login', [StaffController::class,'loginn'])->name('staff.login.handle');
Route::post('/admin/login', [AdminController::class,'loginn'])->name('admin.login.handle');


Route::middleware('StaffSales')->group(function () {
   Route::get('/staff/sales/dashboard', function () {
        return view('dashboards.staff.sales.dasboardSales');
    })->name('sales.dashboard');

    });
    Route::middleware('staff')->group(function () {
    Route::get('/staff/operations/dashboard', [StaffOperations::class, 'showOperation'])
    ->name('operatoins.dashboard')->middleware('permission:operations.delete');
    Route::get('/staff/operations', [StaffController::class, 'showdashboard'])
    ->name('dashboard');
   Route::post('/staff/logout', [StaffController::class, 'logout'])
    ->name('staff.logout');
   Route::get('/staff/operation/create/{trip}', [StaffOperations::class, 'store'])->name('create.operations');
   Route::get('/staff/operation/createOperations', [StaffOperations::class, 'showCreatedOperations'])->name('created.operations');
    Route::post('/staff/operation/cancel', [StaffOperations::class, 'cancelOperation'])->name('cancel.operations');
    Route::post('/staff/operation/company-prices', [StaffOperations::class, 'updateCompanyPrices'])->name('company.prices');
    Route::post('/staff/operation/save', [StaffOperations::class, 'saveOperation'])->name('save.operations');
   Route::POST('/staff/operation/storeCustomer', [StaffOperations::class, 'storeCustomer'])->name('store.Customer');
   Route::get('/ticket/{ticket}/pdf', [StaffOperations::class, 'ticketPdf'])
    ->name('operation.ticket.pdf');
   Route::get('/ticket/{ticket}/download', [StaffOperations::class, 'ticketDownloadPage'])
    ->name('operation.ticket.download');
Route::get('/daily-ops', [DailyOpsController::class, 'index'])->name('daily-ops.index');
Route::resource('/operation-assignments', OperationAssignmentController::class)->except(['show'])->parameters(['operation-assignments' => 'operationAssignment']);
Route::post('/operation-assignments/post-to-accounting', [OperationAssignmentController::class, 'postToAccounting'])->name('operation-assignments.post-to-accounting');
Route::post('/daily-ops/boats', [DailyOpsController::class, 'storeBoat'])->name('daily-ops.boats.store');
Route::post('/daily-ops/save', [DailyOpsController::class, 'save'])->name('daily-ops.save');
Route::post('/print/save', [StaffController::class, 'showPrint'])->name('print.save');
    });

Route::get('/ticket/{ticket}', [StaffOperations::class, 'showTicket'])
    ->name('operation.ticket');


    Route::middleware('StaffReseller')->group(function () {

    Route::get('/staff/reseller/dashboard', function () {
        return view('dashboards.staff.Reseller.dashboardReseller');
    })->name('reseller.dashboard');
    });

Route::middleware('accounting-role:admin,accountant,viewer,account_user')->group(function () {

    Route::get('/accounting', [AccountingController::class, 'dashboard'])->name('accounting.home');
    Route::get('/employee-custody', [EmployeeCustodyController::class, 'index'])
        ->middleware('accounting-role:admin,accountant')
        ->name('employee-custody.index');
    Route::get('/boat-revenues', [BoatRevenueController::class, 'index'])
        ->name('boat-revenues.index');
    Route::get('/vehicle-revenues', [VehicleRevenueController::class, 'index'])
        ->name('vehicle-revenues.index');
    Route::post('/employee-custody/deposit', [EmployeeCustodyController::class, 'deposit'])
        ->middleware('accounting-role:admin,accountant')
        ->name('employee-custody.deposit');
    Route::redirect('/home', '/accounting')->name('accounting.home.alias');
    Route::post('/logout', [AccountingController::class, 'logout'])->name('accounting.logout');
    Route::post('/account-user/logout', [AccountingController::class, 'logout'])->name('account-user.logout');
    Route::get('/accounts', [AccountingController::class, 'page'])->defaults('view', 'accounts')->name('accounts');
    Route::get('/accounts/tree', [AccountingController::class, 'page'])->defaults('view', 'accounts_tree')->name('trees');
    Route::get('/journal', [AccountingController::class, 'page'])->defaults('view', 'journal')->name('accounting.journal');
    Route::get('/ledger', [AccountingController::class, 'page'])->defaults('view', 'ledger')->name('accounting.ledger');
    Route::get('/trial-balance', [AccountingController::class, 'page'])->defaults('view', 'trial_balance')->name('accounting.trial-balance');
    Route::get('/budget', [AccountingController::class, 'page'])->defaults('view', 'budget')->name('accounting.budget');
    Route::get('/income-statement', [AccountingController::class, 'page'])->defaults('view', 'income_statement')->name('accounting.income-statement');
    Route::get('/balance-sheet', [AccountingController::class, 'page'])->defaults('view', 'balance_sheet')->name('accounting.balance-sheet');
    Route::get('/cash-flow', [AccountingController::class, 'page'])->defaults('view', 'cash_flow')->name('accounting.cash-flow');
    Route::get('/help', [AccountingController::class, 'page'])->defaults('view', 'help')->name('accounting.help');
    Route::get('/settings', [AccountingController::class, 'page'])->defaults('view', 'settings')->name('accounting.settings');
    Route::get('/regions/manage', [AccountingController::class, 'page'])->defaults('view', 'regions')->middleware('accounting-role:admin')->name('accounting.regions');
    Route::get('/trips/hotels', [AccountingController::class, 'page'])->defaults('view', 'trip_hotels')->middleware('accounting-role:admin')->name('accounting.trip-hotels');
    Route::get('/users', [AccountingController::class, 'usersPage'])->middleware('accounting-role:admin')->name('accounting.users');
    Route::get('/audit', [AccountingController::class, 'auditPage'])->middleware('accounting-role:admin')->name('accounting.audit');
    Route::get('/admin/user-permissions', [UserPermissionController::class, 'index'])
        ->middleware('accounting-role:admin')
        ->name('admin.user-permissions');
    Route::post('/admin/user-permissions', [UserPermissionController::class, 'update'])
        ->middleware('accounting-role:admin')
        ->name('admin.user-permissions.update');

    Route::prefix('api')->group(function () {
        Route::get('/home', [AccountingController::class, 'dashboardData']);
        Route::get('/accounts', [AccountingController::class, 'accounts']);
        Route::post('/accounts', [AccountingController::class, 'createAccount'])->middleware('accounting-role:admin,accountant,account_user');
        Route::put('/accounts/{id}', [AccountingController::class, 'updateAccount'])->middleware('accounting-role:admin,accountant,account_user');
        Route::delete('/accounts/{id}', [AccountingController::class, 'deleteAccount'])->middleware('accounting-role:admin,accountant,account_user');
        Route::post('/accounts/wipe', [AccountingController::class, 'wipeAccounts'])->middleware('accounting-role:admin');
        Route::post('/accounts/budget', [AccountingController::class, 'updateBudgets'])->middleware('accounting-role:admin,accountant,account_user');
        Route::post('/accounts/import', [AccountingController::class, 'importAccounts'])->middleware('accounting-role:admin,accountant,account_user');
        Route::get('/accounts/tree', [AccountingController::class, 'accountsTree']);
        Route::get('/next-movement-no', [AccountingController::class, 'nextMovementNo'])->middleware('accounting-role:admin,accountant,account_user');
        Route::get('/journal', [AccountingController::class, 'journal']);
        Route::post('/journal', [AccountingController::class, 'createJournal'])->middleware('accounting-role:admin,accountant,account_user');
        Route::put('/journal/{id}', [AccountingController::class, 'updateJournal'])->middleware('accounting-role:admin,accountant,account_user');
        Route::delete('/journal/{id}', [AccountingController::class, 'deleteJournal'])->middleware('accounting-role:admin,accountant,account_user');
        Route::post('/journal/bulk-delete', [AccountingController::class, 'bulkDeleteJournal'])->middleware('accounting-role:admin');
        Route::post('/journal/{id}/post', [AccountingController::class, 'postJournal'])->middleware('accounting-role:admin,accountant,account_user');
        Route::post('/journal/{id}/unpost', [AccountingController::class, 'unpostJournal'])->middleware('accounting-role:admin,accountant,account_user');
        Route::post('/journal/import', [AccountingController::class, 'importJournal'])->middleware('accounting-role:admin,accountant,account_user');
        Route::get('/ledger', [AccountingController::class, 'ledger']);
        Route::get('/trial-balance', [AccountingController::class, 'trialBalance']);
        Route::get('/budget', [AccountingController::class, 'budget']);
        Route::get('/income-statement', [AccountingController::class, 'incomeStatement']);
        Route::get('/balance-sheet', [AccountingController::class, 'balanceSheet']);
        Route::get('/cash-flow', [AccountingController::class, 'cashFlow']);

        Route::get('/regions', [AccountingController::class, 'regions']);
        Route::get('/region-hotels', [AccountingController::class, 'regionHotels'])->middleware('accounting-role:admin');
        Route::get('/region-trips', [AccountingController::class, 'regionTrips'])->middleware('accounting-role:admin');
        Route::get('/trip-hotels', [AccountingController::class, 'tripHotels'])->middleware('accounting-role:admin');
        Route::put('/trips/{id}/hotels', [AccountingController::class, 'updateTripHotels'])->middleware('accounting-role:admin');
        Route::put('/hotels/{id}/region', [AccountingController::class, 'assignHotelRegion'])->middleware('accounting-role:admin');
        Route::put('/trips/{id}/region', [AccountingController::class, 'assignTripRegion'])->middleware('accounting-role:admin');
        Route::post('/regions', [AccountingController::class, 'createRegion'])->middleware('accounting-role:admin');
        Route::put('/regions/{id}', [AccountingController::class, 'renameRegion'])->middleware('accounting-role:admin');
        Route::post('/regions/{id}/deactivate', [AccountingController::class, 'deactivateRegion'])->middleware('accounting-role:admin');
        Route::post('/regions/{id}/activate', [AccountingController::class, 'activateRegion'])->middleware('accounting-role:admin');
        Route::delete('/regions/{id}', [AccountingController::class, 'deleteRegion'])->middleware('accounting-role:admin');
        Route::post('/settings', [AccountingController::class, 'saveSettings'])->middleware('accounting-role:admin');
        Route::post('/backup', [AccountingController::class, 'createBackup'])->middleware('accounting-role:admin');
        Route::get('/backups', [AccountingController::class, 'backups'])->middleware('accounting-role:admin');
        Route::get('/backups/{name}', [AccountingController::class, 'downloadBackup'])->middleware('accounting-role:admin');
        Route::post('/restore/{name}', [AccountingController::class, 'restoreBackup'])->middleware('accounting-role:admin');
        Route::post('/restore-file', [AccountingController::class, 'restoreUploadedBackup'])->middleware('accounting-role:admin');
        Route::post('/me/password', [AccountingController::class, 'changePassword']);
        Route::get('/users', [AccountingController::class, 'users'])->middleware('accounting-role:admin');
        Route::post('/users', [AccountingController::class, 'createUser'])->middleware('accounting-role:admin');
        Route::put('/users/{id}', [AccountingController::class, 'updateUser'])->middleware('accounting-role:admin');
        Route::put('/users/{id}/regions', [AccountingController::class, 'updateUserRegions'])->middleware('accounting-role:admin');
        Route::post('/users/{id}/security', [AccountingController::class, 'setUserSecurity'])->middleware('accounting-role:admin');
        Route::post('/users/{id}/password', [AccountingController::class, 'setUserPassword'])->middleware('accounting-role:admin');
        Route::delete('/users/{id}', [AccountingController::class, 'deleteUser'])->middleware('accounting-role:admin');
    });

    Route::get('/accounts/export.xlsx', [AccountingController::class, 'exportAccounts']);
    Route::get('/journal/export.xlsx', [AccountingController::class, 'exportJournal']);
    Route::get('/ledger/export.xlsx', [AccountingController::class, 'exportLedger']);
    Route::get('/trial-balance/export.xlsx', [AccountingController::class, 'exportTrialBalance']);
    Route::get('/budget/export.xlsx', [AccountingController::class, 'exportBudget']);
    Route::get('/income-statement/export.xlsx', [AccountingController::class, 'exportIncomeStatement']);
    Route::get('/balance-sheet/export.xlsx', [AccountingController::class, 'exportBalanceSheet']);
    Route::get('/cash-flow/export.xlsx', [AccountingController::class, 'exportCashFlow']);
    Route::get('/templates/accounts.xlsx', [AccountingController::class, 'accountsTemplate']);
    Route::get('/templates/journal.xlsx', [AccountingController::class, 'journalTemplate']);
});

Route::middleware('admin')->group(function(){
 Route::get('/admin/dashboard', [AdminController::class, 'showdashboard'])
    ->name('admin.dashboard');
});
 Route::middleware('accounting-role:admin')->group(function () {
     Route::get('/operation-resources/trips-hotels', [OperationResourcesController::class, 'tripsAndHotels'])->name('accounting.trips-hotels');
     Route::get('/operation-resources/trips', [OperationResourcesController::class, 'trips'])->name('accounting.trips');
     Route::get('/operation-resources/hotels', [OperationResourcesController::class, 'hotels'])->name('accounting.hotels');
     Route::post('/operation-resources/trips', [OperationResourcesController::class, 'storeTrip'])->name('accounting.trips.store');
     Route::put('/operation-resources/trips/{trip}', [OperationResourcesController::class, 'updateTrip'])->name('accounting.trips.update');
     Route::delete('/operation-resources/trips/{trip}', [OperationResourcesController::class, 'archiveTrip'])->name('accounting.trips.destroy');
     Route::post('/operation-resources/hotels', [OperationResourcesController::class, 'storeHotel'])->name('accounting.hotels.store');
     Route::put('/operation-resources/hotels/{hotel}', [OperationResourcesController::class, 'updateHotel'])->name('accounting.hotels.update');
     Route::delete('/operation-resources/hotels/{hotel}', [OperationResourcesController::class, 'archiveHotel'])->name('accounting.hotels.destroy');
     Route::get('/operation-resources/suppliers', [OperationResourcesController::class, 'suppliers'])->name('accounting.suppliers');
     Route::post('/operation-resources/suppliers', [OperationResourcesController::class, 'storeSupplier'])->name('accounting.suppliers.store');
     Route::put('/operation-resources/suppliers/{supplier}', [OperationResourcesController::class, 'updateSupplier'])->name('accounting.suppliers.update');
     Route::delete('/operation-resources/suppliers/{supplier}', [OperationResourcesController::class, 'archiveSupplier'])->name('accounting.suppliers.destroy');
     Route::get('/operation-resources/companies', [OperationResourcesController::class, 'companies'])->name('accounting.companies');
     Route::post('/operation-resources/companies', [OperationResourcesController::class, 'storeCompany'])->name('accounting.companies.store');
     Route::put('/operation-resources/companies/{company}', [OperationResourcesController::class, 'updateCompany'])->name('accounting.companies.update');
     Route::delete('/operation-resources/companies/{company}', [OperationResourcesController::class, 'archiveCompany'])->name('accounting.companies.destroy');
     Route::get('/operation-resources/extras', [OperationResourcesController::class, 'extras'])->name('accounting.extras');
     Route::post('/operation-resources/extras', [OperationResourcesController::class, 'storeExtra'])->name('accounting.extras.store');
     Route::put('/operation-resources/extras/{extra}', [OperationResourcesController::class, 'updateExtra'])->name('accounting.extras.update');
     Route::delete('/operation-resources/extras/{extra}', [OperationResourcesController::class, 'archiveExtra'])->name('accounting.extras.destroy');
     Route::get('/operation-resources/company-prices', [OperationResourcesController::class, 'companyPrices'])->name('accounting.company-prices');
     Route::post('/operation-resources/company-prices', [OperationResourcesController::class, 'saveCompanyPrice'])->name('accounting.company-prices.save');
     Route::get('/operation-resources/boats', [OperationResourcesController::class, 'boats'])->name('accounting.boats');
     Route::post('/operation-resources/boats', [OperationResourcesController::class, 'storeBoat'])->name('accounting.boats.store');
     Route::put('/operation-resources/boats/{boat}', [OperationResourcesController::class, 'updateBoat'])->name('accounting.boats.update');
     Route::delete('/operation-resources/boats/{boat}', [OperationResourcesController::class, 'deleteBoat'])->name('accounting.boats.destroy');
     Route::get('/operation-resources/vehicles', [OperationResourcesController::class, 'vehicles'])->name('accounting.vehicles');
     Route::post('/operation-resources/vehicles', [OperationResourcesController::class, 'storeVehicle'])->name('accounting.vehicles.store');
     Route::put('/operation-resources/vehicles/{vehicle}', [OperationResourcesController::class, 'updateVehicle'])->name('accounting.vehicles.update');
     Route::delete('/operation-resources/vehicles/{vehicle}', [OperationResourcesController::class, 'deleteVehicle'])->name('accounting.vehicles.destroy');
     Route::get('/operation-resources/drivers', [OperationResourcesController::class, 'drivers'])->name('accounting.drivers');
     Route::post('/operation-resources/drivers', [OperationResourcesController::class, 'storeDriver'])->name('accounting.drivers.store');
     Route::put('/operation-resources/drivers/{driver}', [OperationResourcesController::class, 'updateDriver'])->name('accounting.drivers.update');
     Route::delete('/operation-resources/drivers/{driver}', [OperationResourcesController::class, 'archiveDriver'])->name('accounting.drivers.destroy');
     Route::put('/operation-resources/drivers/{driver}/restore', [OperationResourcesController::class, 'restoreDriver'])->name('accounting.drivers.restore');
     Route::get('/operation-resources/boat-drivers', [OperationResourcesController::class, 'boatDrivers'])->name('accounting.boat-drivers');
     Route::post('/operation-resources/boat-drivers', [OperationResourcesController::class, 'storeBoatDriver'])->name('accounting.boat-drivers.store');
     Route::put('/operation-resources/boat-drivers/{driver}', [OperationResourcesController::class, 'updateBoatDriver'])->name('accounting.boat-drivers.update');
     Route::delete('/operation-resources/boat-drivers/{driver}', [OperationResourcesController::class, 'archiveBoatDriver'])->name('accounting.boat-drivers.destroy');
     Route::put('/operation-resources/boat-drivers/{driver}/restore', [OperationResourcesController::class, 'restoreBoatDriver'])->name('accounting.boat-drivers.restore');
 });
 Route::middleware('supervisor')->group(function(){
   Route::get('/supervisor/dashboard', function () {
        return view('dashboards.supervisor.dashboard');
    })->name('supervisor.dashboard');
});
