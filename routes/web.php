<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\InvoiceItemsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Services\ClientService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function (ClientService $clientService) {
    $user = auth()->user();
    
    return Inertia::render('Dashboard', [
        'stats' => $clientService->getStatsForUser($user)
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/email-settings', [ProfileController::class, 'updateEmailSettings'])->name('profile.email-settings.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    Route::resource('invoices', InvoicesController::class);
    Route::resource('recurring-invoices', RecurringInvoiceController::class);
    
    Route::prefix('invoices/{invoice}')->group(function () {
        Route::post('items', [InvoiceItemsController::class, 'store'])->name('invoices.items.store');
        Route::put('items/{item}', [InvoiceItemsController::class, 'update'])->name('invoices.items.update');
        Route::delete('items/{item}', [InvoiceItemsController::class, 'destroy'])->name('invoices.items.destroy');
        Route::post('payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
        Route::get('download', [InvoicesController::class, 'download'])->name('invoices.download');
    });

    Route::prefix('recurring-invoices/{recurring_invoice}')->group(function () {
        Route::post('pause', [RecurringInvoiceController::class, 'pause'])->name('recurring-invoices.pause');
        Route::post('resume', [RecurringInvoiceController::class, 'resume'])->name('recurring-invoices.resume');
        Route::post('cancel', [RecurringInvoiceController::class, 'cancel'])->name('recurring-invoices.cancel');
    });
});

require __DIR__.'/auth.php';
