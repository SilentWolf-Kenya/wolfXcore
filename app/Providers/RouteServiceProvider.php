<?php

namespace Pterodactyl\Providers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Database;
use Pterodactyl\Enum\ResourceLimit;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Pterodactyl\Http\Middleware\TrimStrings;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected const FILE_PATH_REGEX = '/^\/api\/client\/servers\/([a-z0-9-]{36})\/files(\/?$|\/(.)*$)/i';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Disable trimming string values when requesting file information — it isn't helpful
        // and messes up the ability to actually open a directory that ends with a space.
        TrimStrings::skipWhen(function (Request $request) {
            return preg_match(self::FILE_PATH_REGEX, $request->getPathInfo()) === 1;
        });

        // This is needed to make use of the "resolveRouteBinding" functionality in the
        // model. Without it you'll never trigger that logic flow thus resulting in a 404
        // error because we request databases with a HashID, and not with a normal ID.
        Route::model('database', Database::class);

        $this->routes(function () {
            Route::middleware('web')->group(function () {
                // Public landing page — no auth required
                Route::get('/', [\wolfXcore\Http\Controllers\LandingController::class, 'index'])
                    ->name('landing');

                Route::middleware(['auth.session', RequireTwoFactorAuthentication::class])
                    ->group(base_path('routes/base.php'));

                // Super Admin routes — entirely outside the admin auth middleware
                // Auth pages are fully public; panel/actions are guarded by SuperAdminMiddleware (session key)
                Route::prefix('/admin/wxn-super')->group(function () {
                    Route::get('/auth', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'showAuth'])->name('admin.super.auth');
                    Route::post('/auth', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'authenticate'])->name('admin.super.authenticate');
                });
                Route::prefix('/admin/wxn-super')
                    ->middleware([\wolfXcore\Http\Middleware\SuperAdminMiddleware::class])
                    ->group(function () {
                        Route::get('/panel', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'index'])->name('admin.super.index');
                        Route::post('/toggle/{user}', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'toggleAdmin'])->name('admin.super.toggle');
                        Route::post('/logout', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'logout'])->name('admin.super.logout');
                        Route::post('/branding', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'updateBranding'])->name('admin.super.branding');
                        Route::post('/theme', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'updateTheme'])->name('admin.super.theme');
                        Route::post('/tabs', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'updateTabs'])->name('admin.super.tabs');
                        Route::post('/notifications', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'createNotification'])->name('admin.super.notifications.create');
                        Route::post('/notifications/{id}/delete', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'deleteNotification'])->name('admin.super.notifications.delete');
                        Route::post('/notifications/{id}/toggle', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'toggleNotification'])->name('admin.super.notifications.toggle');
                        Route::post('/payment/save', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'savePaymentSettings'])->name('admin.super.payment.save');
                        Route::post('/provisioning/save', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'saveProvisioningSettings'])->name('admin.super.provisioning.save');
                        Route::post('/maintenance', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'toggleMaintenance'])->name('admin.super.maintenance');
                        Route::post('/announcement', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'saveAnnouncement'])->name('admin.super.announcement');
                        Route::post('/christmas/save', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'saveChristmasSettings'])->name('admin.super.christmas.save');
                        Route::post('/repo-clone/save', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'saveRepoCloneSettings'])->name('admin.super.repo-clone.save');
                        Route::get('/bot-health', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'botHealth'])->name('admin.super.bot-health');
                        Route::post('/bot-health/{serverId}/reset', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'resetBotHealth'])->name('admin.super.bot-health.reset');
                        Route::get('/paystack-transactions', [\wolfXcore\Http\Controllers\Admin\SuperAdminController::class, 'paystackTransactions'])->name('admin.super.paystack-transactions');
                    });

                Route::middleware(['auth.session', RequireTwoFactorAuthentication::class, AdminAuthenticate::class])
                    ->prefix('/admin')
                    ->group(base_path('routes/admin.php'));

                Route::middleware('guest')->prefix('/auth')->group(base_path('routes/auth.php'));
            });

            Route::middleware(['api', RequireTwoFactorAuthentication::class])->group(function () {
                Route::middleware(['application-api', 'throttle:api.application'])
                    ->prefix('/api/application')
                    ->scopeBindings()
                    ->group(base_path('routes/api-application.php'));

                Route::middleware(['client-api', 'throttle:api.client'])
                    ->prefix('/api/client')
                    ->scopeBindings()
                    ->group(base_path('routes/api-client.php'));
            });

            Route::middleware('daemon')
                ->prefix('/api/remote')
                ->scopeBindings()
                ->group(base_path('routes/api-remote.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Authentication rate limiting. For login and checkpoint endpoints we'll apply
        // a limit of 10 requests per minute, for the forgot password endpoint apply a
        // limit of two per minute for the requester so that there is less ability to
        // trigger email spam.
        RateLimiter::for('authentication', function (Request $request) {
            if ($request->route()->named('auth.post.forgot-password')) {
                return Limit::perMinute(2)->by($request->ip());
            }

            return Limit::perMinute(10);
        });

        // Configure the throttles for both the application and client APIs below.
        // This is configurable per-instance in "config/http.php". By default this
        // limiter will be tied to the specific request user, and falls back to the
        // request IP if there is no request user present for the key.
        //
        // This means that an authenticated API user cannot use IP switching to get
        // around the limits.
        RateLimiter::for('api.client', function (Request $request) {
            $key = optional($request->user())->uuid ?: $request->ip();

            return Limit::perMinutes(
                config('http.rate_limit.client_period'),
                config('http.rate_limit.client')
            )->by($key);
        });

        RateLimiter::for('api.application', function (Request $request) {
            $key = optional($request->user())->uuid ?: $request->ip();

            return Limit::perMinutes(
                config('http.rate_limit.application_period'),
                config('http.rate_limit.application')
            )->by($key);
        });

        ResourceLimit::boot();
    }
}
