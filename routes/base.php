<?php

use Illuminate\Support\Facades\Route;
use wolfXcore\Http\Controllers\Base;
use wolfXcore\Http\Controllers\BillingController;
use wolfXcore\Http\Controllers\BotController;
use wolfXcore\Http\Middleware\RequireTwoFactorAuthentication;

Route::get('/dashboard', [Base\IndexController::class, 'index'])->name('index');
Route::get('/account', [Base\IndexController::class, 'index'])
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

Route::get('/locales/locale.json', Base\LocaleController::class)
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
    ->where('namespace', '.*');

Route::prefix('billing')->group(function () {
    Route::get('/',             [BillingController::class, 'index'])->name('billing.index');
    Route::post('/initiate',    [BillingController::class, 'initiate'])->name('billing.initiate');
    Route::post('/verify',      [BillingController::class, 'verify'])->name('billing.verify');
    Route::get('/callback',     [BillingController::class, 'callback'])->name('billing.callback');
    Route::post('/wallet-pay',              [BillingController::class, 'walletPay'])->name('billing.wallet_pay');
    Route::post('/wallet/deposit/initiate', [BillingController::class, 'initiateWalletDeposit'])->name('billing.wallet_deposit_initiate');
    Route::post('/wallet/deposit/verify',   [BillingController::class, 'verifyWalletDeposit'])->name('billing.wallet_deposit_verify');

});

Route::prefix('bots')->group(function () {
    Route::get('/',                     [BotController::class, 'index'])->name('bots.index');
    Route::get('/configure/{uuid}',     [BotController::class, 'configure'])->name('bots.configure');
    Route::post('/configure/{uuid}',    [BotController::class, 'saveConfig'])->name('bots.save_config');
    Route::post('/initiate',            [BotController::class, 'initiate'])->name('bots.initiate');
    Route::post('/verify',              [BotController::class, 'verify'])->name('bots.verify');
});

Route::get('/{react}', [Base\IndexController::class, 'index'])
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon|billing|bots)).+');
