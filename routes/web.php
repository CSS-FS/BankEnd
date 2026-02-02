<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BreedController;
use App\Http\Controllers\Web\ChartController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\DailyReportsController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExpenseHeadController;
use App\Http\Controllers\Web\FarmController;
use App\Http\Controllers\Web\FarmExpenseController;
use App\Http\Controllers\Web\FarmManagerController;
use App\Http\Controllers\Web\FeedController;
use App\Http\Controllers\Web\FlockController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\IotController;
use App\Http\Controllers\Web\LogsController;
use App\Http\Controllers\Web\MapController;
use App\Http\Controllers\Web\MedicineController;
use App\Http\Controllers\Web\NewsletterController;
use App\Http\Controllers\Web\NewsletterSubscriberController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PartnerController;
use App\Http\Controllers\Web\PricingController;
use App\Http\Controllers\Web\ProductionLogController;
use App\Http\Controllers\Web\PushNotificationController;
use App\Http\Controllers\Web\QrCodeController;
use App\Http\Controllers\Web\ReportsController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\ShedController;
use App\Http\Controllers\Web\ShortcutController;
use App\Http\Controllers\Web\StaffController;
use App\Http\Controllers\Web\TopicController;
use App\Http\Controllers\Web\WebDeviceTokenController;
use App\Http\Controllers\Web\WebSettingController;
use App\Models\Flock;
use App\Models\Partner;
use App\Models\Shed;
use App\Services\OutdoorEnvironmentalDataService;
use App\Services\ShedAnalyticsService;
use Illuminate\Support\Facades\Route;

// Frontend Routes
Route::get('/', function () {
    return view('frontend.index');
});

Route::get('/features', function () {
    return view('frontend.features');
})->name('features');

Route::get('/partners', function () {
    $partners = Partner::with('media')
        ->where('is_active', true)
        ->get();

    return view('frontend.partners', compact('partners'));
})->name('partners');

Route::get('/blogs', function () {
    return view('frontend.blog');
})->name('blogs');

Route::get('/events', function () {
    return view('frontend.events');
})->name('events');

Route::get('/pricing', function () {
    return view('frontend.pricing');
})->name('pricing');

Route::get('/about', function () {
    return view('frontend.about');
})->name('about');

Route::get('/terms-privacy', function () {
    return view('frontend.terms_privacy');
})->name('privacy');

Route::post('/newsletter/subscribe', [NewsletterSubscriberController::class, 'store'])
    ->name('newsletter.subscribe');

Route::get('/newsletter/unsubscribe/{token}', [NewsletterSubscriberController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

// Login and forget-password routes (outside admin group)
Route::get('/login', function () {
    return view('frontend.login');
});
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::get('/register', function () {
    return view('frontend.register');
});

Route::post('/register', [AuthController::class, 'register'])->name('client.register');

// Force Reset Password
Route::get('/required-reset/{user}', function ($user) {
    return view('frontend.force-reset', compact('user'));
})->name('required.reset');

Route::put('/force-reset/{user}', [AuthController::class, 'forceReset'])->name('force.reset');

Route::get('/forget-password', function () {
    return view('frontend.forgot');
});
Route::post('/forget-password', [AuthController::class, 'forgotPassword'])->name('forget');

// Password reset routes
Route::get('/reset-password/{token}', function ($token) {
    $email = request('email');

    return view('frontend.reset', compact('token', 'email'));
})->name('password.reset');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Email verification routes
Route::get('/email/verify', function () {
    return view('auth.verification');
})->middleware('auth')->name('verification.notice');

// Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
//    ->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Impersonation routes (requires authentication)
Route::group(['middleware' => 'auth'], function () {
    Route::impersonate();

    Route::get('/sync-outdoor', function () {
        $svc = new OutdoorEnvironmentalDataService;
        $affected = $svc->fetchAndStore(
            farm_id: 1,
            latitude: 31.9072,
            longitude: 74.2356,
            pastDays: 60
        );

        return response()->json([
            'message' => 'Outdoor environmental data synced.',
            'affected_rows' => $affected,
        ]);
    });

    Route::get('/admin-shortcuts', [ShortcutController::class, 'getAdminShortcuts']);
    Route::get('/user-shortcuts', [ShortcutController::class, 'getUserShortcuts']);
    Route::get('/my-shortcuts', [ShortcutController::class, 'getUserPersonalizedShortcuts']);
    Route::get('/shortcuts/{group}', [ShortcutController::class, 'getShortcutsByGroup']);

    Route::get('/dashboard-stats/{shedId}', function ($shedId) {
        $shedService = new ShedAnalyticsService($shedId);

        return response()->json(
            [
                'overview' => $shedService->ShedOverview(),
                'monitoring' => $shedService->EnvironmentalMonitoring(),
                'health' => $shedService->FlockHealth(),
                'efficiency' => $shedService->OperationalEfficiency(),
            ]);
    });
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin']], function () {
    // Newsletters
    Route::get('newsletters/subscribers', [NewsletterSubscriberController::class, 'index'])
        ->name('newsletters.subscribers');
    Route::put('newsletters/subscribers/{subscriber}/toggle', [NewsletterSubscriberController::class, 'toggleStatus'])
        ->name('newsletters.subscribers.toggle');
    Route::resource('newsletters', NewsletterController::class);
    Route::post('newsletters/{newsletter}/queue-send', [NewsletterController::class, 'queueSend'])
        ->name('newsletters.queueSend');

    Route::get('/iot/alerts', [LogsController::class, 'alerts'])->name('iot.alerts');
    Route::get('/iot/events-data/{id}', [LogsController::class, 'events_data'])->name('iot.events.data');
    Route::get('/devices/map', [MapController::class, 'showDeviceMap'])->name('devices.map');

    // Settings
    Route::prefix('system')->group(function () {
        Route::apiResource('/web-settings', WebSettingController::class);
        Route::get('/web-settings/public', [WebSettingController::class, 'publicSettings'])->name('system.settings.public');
        Route::get('/web-settings/general', [WebSettingController::class, 'general'])->name('system.settings.general');
        Route::get('/web-settings/company', [WebSettingController::class, 'companySettings'])->name('system.settings.company');
        Route::get('/web-settings/social', [WebSettingController::class, 'socialSettings'])->name('system.settings.social');
        Route::get('/web-settings/contact', [WebSettingController::class, 'contactSettings'])->name('system.settings.contact');
        Route::get('/web-settings/{group}', [WebSettingController::class, 'byGroup'])->name('system.settings.group');
        Route::put('/web-settings/bulk', [WebSettingController::class, 'bulkUpdate'])->name('system.settings.bulk');
    });

    // Resource routes for clients and charts
    Route::resources([
        'shortcuts' => ShortcutController::class,
        'breeding' => BreedController::class,
        'feeds' => FeedController::class,
        'pricing-plans' => PricingController::class,
        'partners' => PartnerController::class,
    ]);

    // Partner Routes
    Route::prefix('partners')->controller(PartnerController::class)->group(function () {
        Route::post('/{partner}/add-keyword', 'addKeyword');
        Route::post('/{partner}/remove-keyword', 'removeKeyword');
        Route::get('partners-keywords', 'getAllKeywords');
        Route::patch('/{partner}/toggle-status', [PartnerController::class, 'toggleStatus'])->name('partners.toggle-status');
        Route::patch('/{partner}/sort', [PartnerController::class, 'updateSort'])->name('partners.update-sort');
    });

    // Users and Clients
    Route::get('user/activities', [ClientController::class, 'activities'])
        ->name('clients.activities');

    Route::prefix('clients')->controller(ClientController::class)->group(function () {
        Route::get('/', 'index')->name('clients.index');
        Route::post('/', 'store')->name('clients.store');
        Route::get('/{user}/edit', 'edit')->name('clients.edit');
        Route::put('/{user}', 'update')->name('clients.update');
        Route::delete('/{user}', 'destroy')->name('clients.destroy');
    });

    // Medicines
    Route::prefix('medicines')->controller(MedicineController::class)->group(function () {
        Route::get('/', 'index')->name('admin.medicines.index');
        Route::post('/', 'store')->name('admin.medicines.store');
        Route::put('/{user}', 'update')->name('admin.medicines.update');
        Route::delete('/{user}', 'destroy')->name('admin.medicines.destroy');
    });

    // Expense Heads
    Route::prefix('expense-heads')->controller(ExpenseHeadController::class)->group(function () {
        Route::get('/', 'index')->name('expense.heads.index');
        Route::post('/', 'store')->name('expense.heads.store');
        Route::get('/{expenseHead}', 'show')->name('expense.heads.show');
        Route::put('/{expenseHead}', 'update')->name('expense.heads.update');
        Route::delete('/{expenseHead}', 'destroy')->name('expense.heads.destroy');
        Route::get('/{expenseHead}/toggle', 'toggle')->name('expense.heads.toggle');
    });

    // Charts (Standard) Data Routes
    Route::prefix('charts')->controller(ChartController::class)->group(function () {
        Route::get('/', 'index')->name('charts.index');
        Route::get('/{chart}/edit', 'edit')->name('charts.edit');
        Route::put('/{chart}', 'update')->name('charts.update');
        Route::delete('/{chart}', 'destroy')->name('charts.destroy');
        Route::post('/import', 'import')->name('charts.import');
        Route::get('/data/{chart}', 'chartData')->name('charts.data');
        Route::get('/{chart}/toggle', 'toggle')->name('charts.toggle');
        Route::post('/data/update', 'data_update')->name('charts.data.update');
    });

    // Roles
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index')->name('roles.index');
        Route::post('/', 'store')->name('roles.store');
        Route::put('/{role}', 'update')->name('roles.update');
        Route::delete('/{role}', 'destroy')->name('roles.destroy');
        Route::get('/{role}/permissions', [RoleController::class, 'getPermissions'])->name('roles.permissions');
        Route::post('/permissions', [RoleController::class, 'setPermissions'])->name('roles.set-permissions');
        Route::get('/{role}/users', [RoleController::class, 'attachedUsers'])->name('roles.users');
    });

    // IoT
    Route::prefix('iot')->controller(IotController::class)->group(function () {
        Route::get('/farm-devices', 'farmDevices')->name('farm.devices');
        Route::post('/farm-devices/link', 'link')->name('farm.devices.link');
        Route::post('/farm-devices/delink', 'delink')->name('farm.devices.delink');

        Route::get('/', 'index')->name('iot.index');
        Route::get('/create', 'create')->name('iot.create');
        Route::post('/', 'store')->name('iot.store');
        Route::get('/{device}', 'show')->name('iot.show');
        Route::get('/{device}/edit', 'edit')->name('iot.edit');
        Route::put('/{device}', 'update')->name('iot.update');
        Route::delete('/{device}', 'destroy')->name('iot.destroy');
        Route::get('/devices/{device}/appliances', 'fetchAppliances');
    });

    // QR Code
    Route::prefix('qr-code')->controller(QrCodeController::class)->group(function () {
        Route::get('/', 'index')->name('qr-code.index');
        Route::post('/print', 'print')->name('qr-code.print');
        Route::get('/download/{device}', 'download')->name('qr-code.download');
    });

    // Notifications
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index')->name('notifications.index');
        Route::post('/mark-all-read', 'markAllAsRead')->name('notifications.mark-all-read');
        Route::post('/{notification}/mark-read', 'markAsRead')->name('notifications.mark-read');
    });

    Route::controller(WebDeviceTokenController::class)->group(function () {
        Route::get('/device-tokens', 'index')
            ->middleware('can:device_tokens.view')
            ->name('device_tokens.index');

        Route::post('/device-tokens/{token}/revoke', 'revoke')
            ->middleware('can:device_tokens.manage')
            ->name('device_tokens.revoke');
    });

    Route::controller(TopicController::class)->group(function () {
        Route::get('/topics', 'index')
            ->middleware('can:topics.view')
            ->name('topics.index');

        Route::post('/topics', 'store')
            ->middleware('can:topics.manage')
            ->name('topics.store');

        Route::put('/topics/{topic}', 'update')
            ->middleware('can:topics.manage')
            ->name('topics.update');
    });

    Route::controller(PushNotificationController::class)->group(function () {
        Route::post('/notifications/enqueue', 'enqueue')
            ->middleware('can:notifications.send')
            ->name('notifications.enqueue');

        Route::get('/notifications/logs', 'logs')
            ->middleware('can:notifications.logs.view')
            ->name('notifications.logs');
    });

    // Productions Logs
    Route::prefix('productions')->controller(ProductionLogController::class)->group(function () {
        Route::get('/create', 'create')->name('productions.create');
        Route::post('/', 'store')->name('productions.store');
        Route::get('/{productionLog}', 'show')->name('productions.show');
        Route::get('/{productionLog}/edit', 'edit')->name('productions.edit');
        Route::put('/{productionLog}', 'update')->name('productions.update');
        Route::delete('/{productionLog}', 'destroy')->name('productions.destroy');
    });
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin|owner']], function () {
    // Farms
    Route::prefix('farms')->controller(FarmController::class)->group(function () {
        Route::get('/', 'index')->name('admin.farms.index');
        Route::post('/', 'store')->name('admin.farms.store');
        Route::get('/{farm}', 'show')->name('admin.farms.show');
        Route::put('/{farm}', 'update')->name('admin.farms.update');
        Route::delete('/{farm}', 'destroy')->name('admin.farms.destroy');
        Route::get('/{farm}/data', 'farmData')->name('farms.data');
        Route::put('/{farm}/assign-manager', 'assignManager');
    });

    // Sheds
    Route::prefix('sheds')->controller(ShedController::class)->group(function () {
        Route::get('/', 'index')->name('admin.sheds.index');
        Route::post('/', 'store')->name('admin.sheds.store');
        Route::get('/{shed}', 'show')->name('admin.sheds.show');
        Route::put('/{shed}', 'update')->name('admin.sheds.update');
        Route::delete('/{shed}', 'destroy')->name('admin.sheds.destroy');
    });

});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin|owner|manager']], function () {

    // Logout route (POST)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('clients')->controller(ClientController::class)->group(function () {
        Route::get('/{user}', 'show')->name('clients.show');
        Route::put('/{user}/update-password', 'updatePassword')->name('user.update-password');
    });

    // Staff management (owner + manager)
    Route::get('/staff', [StaffController::class, 'index'])->name('admin.staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('admin.staff.store');
    Route::get('/staff/{id}', [StaffController::class, 'show'])->name('admin.staff.show'); // AJAX
    Route::put('/staff/{id}', [StaffController::class, 'update'])->name('admin.staff.update');
    Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('admin.staff.destroy');

    // Farm manager assignment (owner only)
    Route::get('/farm-managers', [FarmManagerController::class, 'index'])->name('admin.farm_managers.index');
    Route::post('/farm-managers/create-manager', [FarmManagerController::class, 'createManager'])
        ->name('admin.farm_managers.create_manager');
    Route::post('/farms/{farmId}/manager', [FarmManagerController::class, 'assign'])->name('admin.farms.manager.assign');
    Route::delete('/farms/{farmId}/manager', [FarmManagerController::class, 'unassign'])->name('admin.farms.manager.unassign');

    // Productions Logs
    Route::prefix('productions')->controller(ProductionLogController::class)->group(function () {
        Route::get('/', 'index')->name('productions.index');
        Route::get('/export/excel', 'exportExcel')->name('productions.export.excel');
    });

    Route::get('/iot/logs', [LogsController::class, 'deviceLogs'])->name('iot.logs');
    Route::get('/iot/export/excel', [LogsController::class, 'exportExcel'])->name('iot.export.excel');

    // Daily Reports
    Route::get('/daily-reports', [DailyReportsController::class, 'index'])->name('daily.reports');
    Route::get('/daily-report-card/{version}', [DailyReportsController::class, 'getReportCard'])->name('daily.report.card');

    // Personal Settings
    Route::get('/web-setting/personal', [WebSettingController::class, 'personal'])->name('setting.personal');

    Route::get('/get-sheds', function (\Illuminate\Http\Request $request) {
        return Shed::where('farm_id', $request->farm_id)
            ->select('id', 'name', 'capacity', 'type')
            ->orderBy('name')
            ->get();
    });

    Route::get('/get-flocks', function (\Illuminate\Http\Request $request) {
        return Flock::where('shed_id', $request->shed_id)
            ->select('id', 'name', 'start_date', 'end_date')
            ->orderBy('start_date', 'desc')
            ->get();
    });

    Route::get('/get-devices', function (\Illuminate\Http\Request $request) {
        $shed = Shed::findOrFail($request->shed_id);

        return $shed->devices()
            ->wherePivot('is_active', true)
            ->get();
    });

    Route::get('sheds/{shed}/data', [ShedController::class, 'shedData'])->name('sheds.data');

    // Flocks
    Route::prefix('flocks')->controller(FlockController::class)->group(function () {
        Route::get('/', 'index')->name('admin.flocks.index');
        Route::post('/', 'store')->name('admin.flocks.store');
        Route::get('/{flock}', 'show')->name('admin.flocks.show');
        Route::put('/{flock}', 'update')->name('admin.flocks.update');
        Route::delete('/{flock}', 'destroy')->name('admin.flocks.destroy');
    });

    // Farm Expenses
    Route::prefix('farm-expenses')->controller(FarmExpenseController::class)->group(function () {
        Route::get('/', 'index')->name('farm.expenses.index');
        Route::get('/create', 'create')->name('farm.expenses.create');
        Route::post('/', 'store')->name('farm.expenses.store');
        Route::get('/{farmExpense}/edit', 'edit')->name('farm.expenses.edit');
        Route::get('/{farmExpense}', 'show')->name('farm.expenses.show');
        Route::put('/{farmExpense}', 'update')->name('farm.expenses.update');
        Route::delete('/{farmExpense}', 'destroy')->name('farm.expenses.destroy');
        Route::get('/sheds-by-farm/{farm}', 'getShedsByFarm')->name('farm.expenses.sheds-by-farm');
        Route::get('/flocks-by-shed/{shed}', 'getFlocksByShed')->name('farm.expenses.flocks-by-shed');
    });

    Route::prefix('inventory')->controller(InventoryController::class)->group(function () {
        Route::get('/', 'index')->name('inventory.index');
    });

    // Reports
    Route::prefix('reports')->controller(ReportsController::class)->group(function () {
        Route::get('/income', 'income')->name('reports.income');
        Route::get('/expenses', 'expenses')->name('reports.expenses');
        Route::get('/tax', 'tax')->name('reports.tax');
        Route::get('/devices-sold', 'devices_sales')->name('reports.devices.sales');
        Route::get('/annual', 'annual')->name('reports.annual');
    });
});
