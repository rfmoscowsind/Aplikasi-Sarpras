<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\IncomingGoodsController;
use App\Http\Controllers\PublicBorrowController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitInventoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/units', [UnitController::class, 'index'])->name('units.index');
    Route::get('/units/{unit}', [UnitController::class, 'show'])->name('units.show');
    Route::get('/units/{unit}/qr.svg', [UnitController::class, 'qr'])->name('units.qr');
    Route::post('/units/{unit}/borrow-pin', [UnitController::class, 'updateBorrowPin'])->name('units.borrow-pin');
    Route::post('/units/{unit}/rotate-token', [UnitController::class, 'rotateBorrowToken'])->name('units.rotate-token');

    Route::get('/units/{unit}/inventory', [UnitInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/units/{unit}/inventory/existing', [UnitInventoryController::class, 'storeExisting'])->name('inventory.existing.store');

    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('/borrowings/{borrowing}', [BorrowingController::class, 'show'])->name('borrowings.show');
    Route::post('/borrowings/{borrowing}/approve', [BorrowingController::class, 'approve'])->name('borrowings.approve');
    Route::post('/borrowings/{borrowing}/reject', [BorrowingController::class, 'reject'])->name('borrowings.reject');
    Route::post('/borrowings/{borrowing}/handover', [BorrowingController::class, 'handOver'])->name('borrowings.handover');

    Route::post('/returns/{returnRequest}/approve', [ReturnRequestController::class, 'approve'])->name('returns.approve');
    Route::post('/returns/{returnRequest}/reject', [ReturnRequestController::class, 'reject'])->name('returns.reject');
    Route::get('/returns/{returnRequest}/photo', [ReturnRequestController::class, 'photo'])->name('returns.photo');

    Route::middleware('role:admin,sarpras')->group(function () {
        Route::get('/incoming', [IncomingGoodsController::class, 'index'])->name('incoming.index');
        Route::get('/incoming/create', [IncomingGoodsController::class, 'create'])->name('incoming.create');
        Route::post('/incoming', [IncomingGoodsController::class, 'store'])->name('incoming.store');
        Route::get('/incoming/{incoming}', [IncomingGoodsController::class, 'show'])->name('incoming.show');
        Route::get('/incoming/{incoming}/invoice', [IncomingGoodsController::class, 'invoice'])->name('incoming.invoice');

        Route::get('/distributions', [DistributionController::class, 'index'])->name('distributions.index');
        Route::get('/distributions/create', [DistributionController::class, 'create'])->name('distributions.create');
        Route::post('/distributions', [DistributionController::class, 'store'])->name('distributions.store');
        Route::get('/distributions/{distribution}', [DistributionController::class, 'show'])->name('distributions.show');
        Route::get('/distributions/{distribution}/letter', [DistributionController::class, 'letter'])->name('distributions.letter');
        Route::post('/distributions/{distribution}/signed-document', [DistributionController::class, 'uploadSigned'])->name('distributions.signed.upload');
        Route::post('/distributions/{distribution}/complete', [DistributionController::class, 'complete'])->name('distributions.complete');
        Route::get('/distributions/{distribution}/signed-document', [DistributionController::class, 'signedDocument'])->name('distributions.signed.download');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::post('/units/{unit}/memberships', [UnitController::class, 'storeMembership'])->name('units.memberships.store');
        Route::delete('/units/{unit}/memberships/{membership}', [UnitController::class, 'destroyMembership'])->name('units.memberships.destroy');
    });
});

Route::middleware('noindex')->prefix('pinjam/{token}')->group(function () {
    Route::get('/', [PublicBorrowController::class, 'start'])->name('public.borrow.start');
    Route::post('/pin', [PublicBorrowController::class, 'verifyPin'])
        ->middleware('throttle:borrow-pin')
        ->name('public.borrow.pin');

    Route::get('/students', [PublicBorrowController::class, 'students'])
        ->middleware('throttle:borrow-lookup')
        ->name('public.borrow.students');
    Route::get('/items', [PublicBorrowController::class, 'items'])
        ->middleware('throttle:borrow-lookup')
        ->name('public.borrow.items');
    Route::get('/active-loans', [PublicBorrowController::class, 'activeLoans'])
        ->middleware('throttle:borrow-lookup')
        ->name('public.borrow.active-loans');

    Route::post('/', [PublicBorrowController::class, 'store'])
        ->middleware('throttle:borrow-submit')
        ->name('public.borrow.store');

    Route::get('/transaksi/{publicId}', [PublicBorrowController::class, 'transaction'])
        ->name('public.borrow.transaction');
    Route::post('/transaksi/{publicId}/return', [PublicBorrowController::class, 'storeReturn'])
        ->middleware('throttle:return-submit')
        ->name('public.borrow.return');
});
