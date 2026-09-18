<?php

use App\Http\Controllers\{AccountController, AnalyticsController, CatalogController, DealsController, FitPassportController, ProductAlertController, ScannerController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShoppingController;
use App\Http\Controllers\WatchController;
use App\Http\Controllers\ShoppingAgentController;
use App\Http\Controllers\PurchaseFeedbackController;

Route::get('/purchases', [PurchaseFeedbackController::class, 'index'])->name('purchases');
Route::post('/purchases/{product}', [PurchaseFeedbackController::class, 'store'])->middleware('throttle:20,1')->name('purchases.store');
Route::delete('/purchases/{feedback}', [PurchaseFeedbackController::class, 'destroy'])->name('purchases.destroy');
Route::get('/go/{offer}', function (Request $request, \App\Models\Offer $offer) {
    abort_unless($offer->is_active && preg_match('#^https?://#i', $offer->product_url), 404);
    app(\App\Services\ProductInteractions::class)->record($request, $offer->product, 'outbound');
    return redirect()->away($offer->product_url);
})->middleware('throttle:60,1')->name('offers.visit');

Route::get('/agent', [ShoppingAgentController::class, 'index'])->name('agent');
Route::post('/agent', [ShoppingAgentController::class, 'analyze'])->middleware('throttle:15,1')->name('agent.analyze');

Route::get('/watch', [WatchController::class, 'index'])->name('watch');
Route::post('/watch/{product}', [WatchController::class, 'store'])->middleware('throttle:10,1')->name('watch.store');
Route::get('/watch/{watch}/verify', [WatchController::class, 'verify'])->middleware('signed')->name('watch.verify');
Route::delete('/watch/{watch}', [WatchController::class, 'destroy'])->name('watch.destroy');
Route::post('/watch-notifications/{notification}/read', [WatchController::class, 'read'])->name('watch.read');

Route::get('/favorites', [ShoppingController::class, 'favorites'])->name('favorites');
Route::post('/favorites/{product}', [ShoppingController::class, 'saveFavorite'])->name('favorites.store');
Route::delete('/favorites/{product}', [ShoppingController::class, 'removeFavorite'])->name('favorites.destroy');
Route::get('/compare', [ShoppingController::class, 'compare'])->name('compare');
Route::post('/compare/{product}', [ShoppingController::class, 'addComparison'])->name('compare.store');
Route::delete('/compare/{product}', [ShoppingController::class, 'removeComparison'])->name('compare.destroy');
Route::get('/my-preferences', [ShoppingController::class, 'preferences'])->name('shopping.preferences');
Route::post('/my-preferences', [ShoppingController::class, 'savePreferences'])->name('shopping.preferences.store');

Route::redirect('/', '/catalog');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
Route::get('/deals', [DealsController::class, 'index'])->name('deals');
Route::get('/fit-passport', [FitPassportController::class, 'index'])->name('fit-passport');
Route::post('/fit-passport/{product}', [FitPassportController::class, 'store'])->name('fit-passport.store');
Route::delete('/fit-passport/{entry}', [FitPassportController::class, 'destroy'])->name('fit-passport.destroy');
Route::get('/alerts', [ProductAlertController::class, 'index'])->name('alerts');
Route::post('/alerts/{product}', [ProductAlertController::class, 'store'])->name('alerts.store');
Route::delete('/alerts/{alert}', [ProductAlertController::class, 'destroy'])->name('alerts.destroy');
Route::get('/account', [AccountController::class, 'show'])->name('account');
Route::post('/account/register', [AccountController::class, 'register'])->name('account.register');
Route::post('/account/login', [AccountController::class, 'login'])->name('account.login');
Route::post('/account/logout', [AccountController::class, 'logout'])->name('account.logout');
Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
Route::post('/scanner', [ScannerController::class, 'scan'])->middleware('throttle:10,1')->name('scanner.scan');
Route::get('/asics', function (Request $request) {
    return redirect('/catalog?'.http_build_query([...$request->query(), 'brand' => 'ASICS']));
});
Route::get('/product/{slug}', [CatalogController::class, 'show'])->name('product.show');
