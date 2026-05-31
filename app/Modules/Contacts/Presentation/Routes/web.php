<?php

use App\Modules\Contacts\Presentation\Controllers\ContactConsentController;
use App\Modules\Contacts\Presentation\Controllers\ContactBlacklistController;
use App\Modules\Contacts\Presentation\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index')->middleware('can:viewAny,'.\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create')->middleware('can:create,'.\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store')->middleware('can:create,'.\App\Modules\Contacts\Infrastructure\Persistence\Models\Contact::class);
Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show')->middleware('can:view,contact');
Route::get('/contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit')->middleware('can:update,contact');
Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update')->middleware('can:update,contact');
Route::post('/contacts/{contact}/consents', [ContactConsentController::class, 'store'])->name('contacts.consents.store')->middleware('can:update,contact');
Route::post('/contacts/{contact}/consents/revoke', [ContactConsentController::class, 'revoke'])->name('contacts.consents.revoke')->middleware('can:update,contact');
Route::post('/contacts/{contact}/blacklist', [ContactBlacklistController::class, 'store'])->name('contacts.blacklist.store')->middleware('can:update,contact');
Route::delete('/contacts/{contact}/blacklist/{blacklist}', [ContactBlacklistController::class, 'destroy'])->name('contacts.blacklist.destroy')->middleware('can:update,contact');
