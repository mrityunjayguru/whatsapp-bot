<?php

use Illuminate\Support\Facades\Route;

// ==============================
// Meta Webhook Endpoints (NO auth, NO CSRF)
// Must come FIRST so resource params don't capture them
// ==============================
Route::get('meta/webhook', [\App\Http\Controllers\MetaWebhookController::class, 'verify'])
    ->name('meta.webhook.verify');

Route::post('meta/webhook', [\App\Http\Controllers\MetaWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('meta.webhook');

// Dashboard — auth required
Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Follow-ups API route
Route::get('dashboard/follow-ups', [\App\Http\Controllers\DashboardController::class, 'followUps'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.follow-ups');

Route::resource('company', \App\Http\Controllers\CompanyController::class)
    ->middleware(['auth', 'verified', 'superadmin'])
    ->except(['show']);

Route::resource('lead-source', \App\Http\Controllers\LeadSourceController::class)
    ->middleware(['auth', 'verified', 'company'])
    ->except(['show']);

// Specific leads routes MUST come before the resource route to avoid {lead} param conflicts
Route::get('leads/export', [\App\Http\Controllers\LeadController::class, 'export'])
    ->middleware(['auth', 'verified', 'company'])
    ->name('leads.export');

Route::get('leads/calendar-events', [\App\Http\Controllers\LeadController::class, 'calendarEvents'])
    ->middleware(['auth', 'verified', 'company'])
    ->name('leads.calendar-events');

Route::resource('leads', \App\Http\Controllers\LeadController::class)
    ->middleware(['auth', 'verified', 'company'])
    ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

Route::get('contacts', [\App\Http\Controllers\ContactController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('contacts.index');

Route::get('contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('contacts.show');

Route::put('contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('contacts.update');

Route::put('contacts/{contact}/tags', [\App\Http\Controllers\ContactController::class, 'updateTags'])
    ->middleware(['auth', 'verified'])
    ->name('contacts.updateTags');

Route::resource('tags', \App\Http\Controllers\TagController::class)
    ->middleware(['auth', 'verified'])
    ->only(['index', 'store']);

Route::resource('conversations', \App\Http\Controllers\ConversationController::class)
    ->middleware(['auth', 'verified'])
    ->only(['index', 'show']);

Route::post('conversations/{conversation}/messages', [\App\Http\Controllers\ConversationController::class, 'sendMessage'])
    ->middleware(['auth', 'verified'])
    ->name('conversations.messages.store');

Route::patch('leads/{lead}/close', [\App\Http\Controllers\LeadController::class, 'close'])
    ->middleware(['auth', 'verified', 'company'])
    ->name('leads.close');


Route::resource('lead-status', \App\Http\Controllers\LeadStatusController::class)
    ->middleware(['auth', 'verified', 'company'])
    ->except(['show']);

Route::resource('interested-in', \App\Http\Controllers\InterestedInController::class)
    ->middleware(['auth', 'verified', 'company'])
    ->except(['show']);

// Roles — managed by company
Route::resource('roles', \App\Http\Controllers\RoleController::class)
    ->middleware(['auth', 'verified', 'company'])
    ->except(['show']);

// Meta Ads Settings — company owner only
Route::get('meta-settings', [\App\Http\Controllers\MetaSettingController::class, 'edit'])
    ->middleware(['auth', 'verified', 'company'])
    ->name('meta-settings.edit');

Route::put('meta-settings', [\App\Http\Controllers\MetaSettingController::class, 'update'])
    ->middleware(['auth', 'verified', 'company'])
    ->name('meta-settings.update');

// Users — managed by company
Route::resource('users', \App\Http\Controllers\UserController::class)
    ->middleware(['auth', 'verified', 'company'])
    ->except(['show']);

Route::group(['prefix' => 'email'], function(){
    Route::get('inbox', function () { return view('pages.email.inbox'); });
    Route::get('read', function () { return view('pages.email.read'); });
    Route::get('compose', function () { return view('pages.email.compose'); });
});

Route::group(['prefix' => 'apps'], function(){
    Route::get('chat', function () { return view('pages.apps.chat'); });
    Route::get('calendar', function () { return view('pages.apps.calendar'); });
});

Route::group(['prefix' => 'ui-components'], function(){
    Route::get('accordion', function () { return view('pages.ui-components.accordion'); });
    Route::get('alerts', function () { return view('pages.ui-components.alerts'); });
    Route::get('badges', function () { return view('pages.ui-components.badges'); });
    Route::get('breadcrumbs', function () { return view('pages.ui-components.breadcrumbs'); });
    Route::get('buttons', function () { return view('pages.ui-components.buttons'); });
    Route::get('button-group', function () { return view('pages.ui-components.button-group'); });
    Route::get('cards', function () { return view('pages.ui-components.cards'); });
    Route::get('carousel', function () { return view('pages.ui-components.carousel'); });
    Route::get('collapse', function () { return view('pages.ui-components.collapse'); });
    Route::get('dropdowns', function () { return view('pages.ui-components.dropdowns'); });
    Route::get('list-group', function () { return view('pages.ui-components.list-group'); });
    Route::get('media-object', function () { return view('pages.ui-components.media-object'); });
    Route::get('modal', function () { return view('pages.ui-components.modal'); });
    Route::get('navs', function () { return view('pages.ui-components.navs'); });
    Route::get('offcanvas', function () { return view('pages.ui-components.offcanvas'); });
    Route::get('pagination', function () { return view('pages.ui-components.pagination'); });
    Route::get('placeholders', function () { return view('pages.ui-components.placeholders'); });
    Route::get('popovers', function () { return view('pages.ui-components.popovers'); });
    Route::get('progress', function () { return view('pages.ui-components.progress'); });
    Route::get('scrollbar', function () { return view('pages.ui-components.scrollbar'); });
    Route::get('scrollspy', function () { return view('pages.ui-components.scrollspy'); });
    Route::get('spinners', function () { return view('pages.ui-components.spinners'); });
    Route::get('tabs', function () { return view('pages.ui-components.tabs'); });
    Route::get('toasts', function () { return view('pages.ui-components.toasts'); });
    Route::get('tooltips', function () { return view('pages.ui-components.tooltips'); });
});

Route::group(['prefix' => 'advanced-ui'], function(){
    Route::get('cropper', function () { return view('pages.advanced-ui.cropper'); });
    Route::get('owl-carousel', function () { return view('pages.advanced-ui.owl-carousel'); });
    Route::get('sortablejs', function () { return view('pages.advanced-ui.sortablejs'); });
    Route::get('sweet-alert', function () { return view('pages.advanced-ui.sweet-alert'); });
});

Route::group(['prefix' => 'forms'], function(){
    Route::get('basic-elements', function () { return view('pages.forms.basic-elements'); });
    Route::get('advanced-elements', function () { return view('pages.forms.advanced-elements'); });
    Route::get('editors', function () { return view('pages.forms.editors'); });
    Route::get('wizard', function () { return view('pages.forms.wizard'); });
});

Route::group(['prefix' => 'charts'], function(){
    Route::get('apex', function () { return view('pages.charts.apex'); });
    Route::get('chartjs', function () { return view('pages.charts.chartjs'); });
    Route::get('flot', function () { return view('pages.charts.flot'); });
    Route::get('peity', function () { return view('pages.charts.peity'); });
    Route::get('sparkline', function () { return view('pages.charts.sparkline'); });
});

Route::group(['prefix' => 'tables'], function(){
    Route::get('basic-tables', function () { return view('pages.tables.basic-tables'); });
    Route::get('data-table', function () { return view('pages.tables.data-table'); });
});

Route::group(['prefix' => 'icons'], function(){
    Route::get('lucide-icons', function () { return view('pages.icons.lucide-icons'); });
    Route::get('flag-icons', function () { return view('pages.icons.flag-icons'); });
    Route::get('mdi-icons', function () { return view('pages.icons.mdi-icons'); });
});

Route::group(['prefix' => 'general'], function(){
    Route::get('blank-page', function () { return view('pages.general.blank-page'); });
    Route::get('invoice', function () { return view('pages.general.invoice'); });
    Route::get('profile', function () { return view('pages.general.profile'); });
    Route::get('pricing', function () { return view('pages.general.pricing'); });
    Route::get('timeline', function () { return view('pages.general.timeline'); });
});

// Removed dummy auth routes

Route::group(['prefix' => 'error'], function(){
    Route::get('404', function () { return view('pages.error.404'); });
    Route::get('500', function () { return view('pages.error.500'); });
});

Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    return "Cache is cleared";
});

// FAQ Management Routes
Route::middleware(['auth'])->group(function () {
    Route::resource('faqs', App\Http\Controllers\FaqController::class);
    Route::patch('faqs/{faq}/toggle-status', [App\Http\Controllers\FaqController::class, 'toggleStatus'])->name('faqs.toggle-status');
});

require __DIR__.'/auth.php';

// 404 for undefined routes
Route::any('/{page?}',function(){
    return View::make('pages.error.404');
})->where('page','.*');
