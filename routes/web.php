<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FollowupController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1'); // per-IP backstop; per-account limit is in AuthController
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account', [UserController::class, 'account'])->name('account');
    Route::put('/account/password', [UserController::class, 'updatePassword'])->name('account.password');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/search', [CompanyController::class, 'search'])->name('search');

    // Leads and customers share the company record
    Route::get('/leads', [CompanyController::class, 'leads'])->name('leads.index');
    Route::get('/leads/create', [CompanyController::class, 'create'])->name('leads.create');
    Route::post('/leads', [CompanyController::class, 'store'])->name('leads.store');
    Route::get('/customers', [CompanyController::class, 'customers'])->name('customers.index');

    Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::post('/companies/{company}/convert', [CompanyController::class, 'convert'])->name('companies.convert');
    Route::post('/companies/{company}/archive', [CompanyController::class, 'archive'])->name('companies.archive');
    Route::post('/companies/{company}/remarks', [CompanyController::class, 'remark'])->name('companies.remarks.store');

    Route::resource('inquiries', InquiryController::class)->except('show');

    Route::resource('followups', FollowupController::class)->except('show');
    Route::post('/followups/{followup}/complete', [FollowupController::class, 'complete'])->name('followups.complete');

    Route::get('/pipeline', PipelineController::class)->name('pipeline');

    // Sales documents: EoI/RFQ, proposals, invoices, payments (all listed on /documents)
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::resource('documents', DocumentController::class)->except(['index', 'show']);
    Route::get('/documents/{document}/files/{index}', [DocumentController::class, 'download'])->whereNumber('index')->name('documents.download');
    Route::resource('proposals', ProposalController::class)->except(['index', 'show']);
    Route::resource('invoices', InvoiceController::class)->except(['index', 'show']);
    Route::resource('payments', PaymentController::class)->only(['create', 'store', 'edit', 'update']);

    Route::get('/pdf/proposal/{proposal}', [PdfController::class, 'proposal'])->name('pdf.proposal');
    Route::get('/pdf/invoice/{invoice}', [PdfController::class, 'invoice'])->name('pdf.invoice');
    Route::get('/pdf/receipt/{payment}', [PdfController::class, 'receipt'])->name('pdf.receipt');

    // Admin only: settings and sales team logins
    Route::middleware('admin')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/list', [SettingController::class, 'addItem'])->name('settings.list.add');
        Route::delete('/settings/list', [SettingController::class, 'removeItem'])->name('settings.list.remove');
        Route::get('/settings/export/{type}', [SettingController::class, 'export'])
            ->whereIn('type', ['leads', 'customers', 'inquiries', 'followups', 'proposals', 'invoices', 'payments'])
            ->name('settings.export');

        Route::post('/settings/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/settings/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/settings/users/{user}', [UserController::class, 'update'])->name('users.update');
    });
});
