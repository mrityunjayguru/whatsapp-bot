<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $contactQuery = \App\Models\Contact::query();
    $convQuery = \App\Models\Conversation::query();

    if (auth()->id() !== 1) {
        $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
        if ($employee && $employee->role !== 'ADMIN') {
            $contactQuery->whereHas('conversations', function($q) use ($employee) {
                $q->where('assigned_tenant_user_id', $employee->id);
            });
            $convQuery->where('assigned_tenant_user_id', $employee->id);
        } elseif (!$employee) {
            $contactQuery->whereHas('conversations', function($q) {
                $q->where('assigned_tenant_user_id', auth()->id());
            });
            $convQuery->where('assigned_tenant_user_id', auth()->id());
        }
    }

    $stats = [
        'total_contacts' => $contactQuery->count(),
        'total_conversations' => $convQuery->count(),
        'total_messages' => \App\Models\Message::count(),
        'active_bots' => \App\Models\ChatBoat::count(),
    ];
    return view('dashboard', compact('stats'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


Route::get('meta/webhook', [\App\Http\Controllers\MetaWebhookController::class, 'verify'])->name('meta.webhook.verify');
Route::post('meta/webhook', [\App\Http\Controllers\MetaWebhookController::class, 'handle'])->middleware('throttle:60,1')->name('meta.webhook');
Route::get('/admin/bot-config', [\App\Http\Controllers\BotConfigController::class, 'edit'])->name('bot-config.edit');
Route::put('/admin/bot-config', [\App\Http\Controllers\BotConfigController::class, 'update'])->name('bot-config.update');
Route::get('contacts', [\App\Http\Controllers\ContactController::class, 'index'])->middleware(['auth', 'verified'])->name('contacts.index');
Route::get('contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'show'])->middleware(['auth', 'verified'])->name('contacts.show');
Route::put('contacts/{contact}', [\App\Http\Controllers\ContactController::class, 'update'])->middleware(['auth', 'verified'])->name('contacts.update');
Route::put('contacts/{contact}/tags', [\App\Http\Controllers\ContactController::class, 'updateTags'])->middleware(['auth', 'verified'])->name('contacts.updateTags');
Route::resource('tags', \App\Http\Controllers\TagController::class)->middleware(['auth', 'verified'])->only(['index', 'store', 'show', 'update', 'destroy']);
Route::resource('employees', \App\Http\Controllers\EmployeeController::class)->middleware(['auth', 'verified']);
Route::resource('companies', \App\Http\Controllers\CompanyController::class)->middleware(['auth', 'verified']);
Route::patch('companies/{company}/toggle-status', [\App\Http\Controllers\CompanyController::class, 'toggleStatus'])->middleware(['auth', 'verified'])->name('companies.toggle-status');
Route::resource('conversations', \App\Http\Controllers\ConversationController::class)->middleware(['auth', 'verified'])->only(['index', 'show']);
Route::put('conversations/{conversation}/assign', [\App\Http\Controllers\ConversationController::class, 'assign'])->middleware(['auth', 'verified'])->name('conversations.assign');
Route::put('conversations/{conversation}/toggle-bot', [\App\Http\Controllers\ConversationController::class, 'toggleBot'])->middleware(['auth', 'verified'])->name('conversations.toggleBot');
Route::post('conversations/{conversation}/messages', [\App\Http\Controllers\ConversationController::class, 'sendMessage'])->middleware(['auth', 'verified'])->name('conversations.messages.store');
Route::put('conversations/{conversation}/status', [\App\Http\Controllers\ConversationController::class, 'updateStatus'])->middleware(['auth', 'verified'])->name('conversations.updateStatus');
Route::middleware(['auth'])->group(function () {
    Route::resource('faqs', App\Http\Controllers\FaqController::class);
    Route::patch('faqs/{faq}/toggle-status', [App\Http\Controllers\FaqController::class, 'toggleStatus'])->name('faqs.toggle-status');
    });
    
Route::get('/api/states/{countryName}', [\App\Http\Controllers\LocationController::class, 'getStates']);
Route::get('widget', [App\Http\Controllers\WidgetController::class, 'index'])->middleware(['auth', 'verified'])->name('widgets.index');
Route::get('widget/create', [App\Http\Controllers\WidgetController::class, 'create'])->middleware(['auth', 'verified'])->name('widgets.create');
Route::post('widget', [App\Http\Controllers\WidgetController::class, 'store'])->middleware(['auth', 'verified'])->name('widgets.store');
Route::get('widget/{token}/edit', [App\Http\Controllers\WidgetController::class, 'edit'])->middleware(['auth', 'verified'])->name('widgets.edit');
Route::put('widget/{token}/config', [App\Http\Controllers\WidgetController::class, 'updateConfig'])->middleware(['auth', 'verified'])->name('widgets.config.update');
Route::delete('widget/{token}', [App\Http\Controllers\WidgetController::class, 'destroy'])->middleware(['auth', 'verified'])->name('widgets.destroy');
Route::post('widget/{token}/faqs', [App\Http\Controllers\WidgetController::class, 'storeFaq'])->middleware(['auth', 'verified'])->name('widgets.faqs.store');
Route::get('widget/{token}/faqs/{sourceId}/edit', [App\Http\Controllers\WidgetController::class, 'editFaq'])->middleware(['auth', 'verified'])->name('widgets.faqs.edit');
Route::put('widget/{token}/faqs/{sourceId}', [App\Http\Controllers\WidgetController::class, 'updateFaq'])->middleware(['auth', 'verified'])->name('widgets.faqs.update');
Route::delete('widget/{token}/faqs/{sourceId}', [App\Http\Controllers\WidgetController::class, 'destroyFaq'])->middleware(['auth', 'verified'])->name('widgets.faqs.destroy');
