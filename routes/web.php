<?php

use App\Http\Controllers\CredentialShareController;

Route::post('credential-share/group', [CredentialShareController::class, 'shareWithGroup'])->name('credential-share.group');

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
