<?php

use App\Http\Controllers\Api\V1\AssetApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\TicketApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Versioned REST API (Sanctum tokens, tenant + policy enforced)
|--------------------------------------------------------------------------
| Tokens carry ability scopes (tickets:read/write, customers:read,
| assets:read, reports:read) and optional expiry — both enforced here.
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->only(['id', 'name', 'email', 'role', 'tenant_id']);
});

Route::prefix('v1')->middleware(['auth:sanctum', 'token.valid', 'tenant', 'throttle:api'])->group(function () {
    Route::get('tickets', [TicketApiController::class, 'index'])->middleware('abilities:tickets:read');
    Route::post('tickets', [TicketApiController::class, 'store'])->middleware('abilities:tickets:write');
    Route::get('tickets/{ticket}', [TicketApiController::class, 'show'])->middleware('abilities:tickets:read');
    Route::post('tickets/{ticket}/replies', [TicketApiController::class, 'reply'])->middleware('abilities:tickets:write');

    Route::get('customers', [CustomerApiController::class, 'index'])->middleware('abilities:customers:read');
    Route::get('customers/{customer}', [CustomerApiController::class, 'show'])->middleware('abilities:customers:read');

    Route::get('assets', [AssetApiController::class, 'index'])->middleware('abilities:assets:read');
    Route::get('assets/{asset}', [AssetApiController::class, 'show'])->middleware('abilities:assets:read');
});
