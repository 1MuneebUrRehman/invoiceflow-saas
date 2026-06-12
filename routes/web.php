<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::view('/', 'welcome')->name('home');

// Stripe webhook — must be exempt from CSRF (handled in bootstrap/app.php)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe');

// Stripe Checkout callback — regenerates the signed invoice URL server-side
// so the client-facing URL never needs to carry a signature.
Route::get('/invoice/{publicId}/payment/{result}', function (string $publicId, string $result) {
    return redirect()->to(
        URL::signedRoute('invoices.public', ['publicId' => $publicId])
    );
})->whereIn('result', ['success', 'cancel'])->name('invoices.payment.callback');

Route::livewire('/invoice/{publicId}', 'pages::invoices.show')
    ->name('invoices.public')
    ->middleware('signed');

Route::middleware('guest')->group(function () {
    Route::livewire('/register', 'pages::auth.register')->name('register');
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('/clients', 'pages::clients.index')->name('clients.index');
    Route::livewire('/clients/create', 'pages::clients.create')->name('clients.create');
    Route::livewire('/clients/{client}/edit', 'pages::clients.edit')->name('clients.edit');

    Route::livewire('/invoices', 'pages::invoices.index')->name('invoices.index');
    Route::livewire('/invoices/create', 'pages::invoices.create')->name('invoices.create');
    Route::livewire('/invoices/{invoice}/edit', 'pages::invoices.edit')->name('invoices.edit');

    Route::livewire('/settings', 'pages::settings')->name('settings');

    // Email verification
    Route::livewire('/email/verify', 'pages::auth.verify-email')->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verify/resend', function () {
        request()->user()->sendEmailVerificationNotification();

        return back();
    })->middleware('throttle:6,1')->name('verification.send');

    Route::post('/logout', LogoutController::class)->name('logout');
});
