<?php

namespace App\Http\Controllers;

use App\Services\WidgetApiService;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function index(WidgetApiService $api)
    {
        $widgets = $api->listWidgets();

        // Company names now come from the widgets table (one row per
        // widget, see the 2026_09_29_000001 migration) so every widget a
        // company owns - not just its first/latest one - shows the right
        // company here.
        $companyNames = \App\Models\Widget::whereNotNull('token')
            ->with('company')
            ->get()
            ->mapWithKeys(fn($w) => [$w->token => $w->company->name ?? '--']);

        foreach ($widgets as &$widget) {
            $widget['company_name'] = $companyNames[$widget['token']] ?? '--';
        }

        if (!is_null((auth()->user()->company_id ?? auth()->user()->tenant_id))) {
            $tokens = \App\Models\Widget::where('company_id', (auth()->user()->company_id ?? auth()->user()->tenant_id))->pluck('token')->all();
            $widgets = collect($widgets)->filter(fn($w) => in_array($w['token'], $tokens, true))->values()->all();
        }

        return view('widgets.index', compact('widgets'));
    }

    public function create()
    {
        $companies = \App\Models\Company::where('bot_usage_type', 'widget')->get();
        return view('widgets.create', compact('companies'));
    }

    public function store(Request $request, WidgetApiService $api)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_name' => 'required|string|max:255',
            'contact_email' => ['nullable', 'email', 'max:255', function ($attribute, $value, $fail) {
                if (\App\Models\Company::where('contact_email', $value)->exists()) {
                    $fail('The contact email must be unique and cannot match a company email.');
                }
            }],
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $widget = $api->createWidget($validated['site_name'], $validated['contact_email'] ?? null);

        if (!$widget) {
            return back()->withInput()->with('error', 'Could not create the widget - check the bot service is running.');
        }

        $company = \App\Models\Company::findOrFail($validated['company_id']);

        // One row per widget, not one column on the company - this is
        // what actually lets a company own more than one widget. Each
        // widget carries its own is_active/valid_from/expiry_date now
        // (matching how the Python side already keys those per-token),
        // instead of every widget of a company being forced to share a
        // single date window.
        \App\Models\Widget::create([
            'company_id' => $company->id,
            'token' => $widget['token'],
            'is_active' => true,
            'valid_from' => $validated['valid_from'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        // Keep the legacy single-widget column in sync only for a
        // company's very first widget, so anything older that still
        // reads companies.widget_token directly (e.g. self-registration)
        // keeps working. A second+ widget must NOT touch it - overwriting
        // it here is exactly the bug that made earlier widgets vanish.
        if (empty($company->widget_token)) {
            $company->update(['widget_token' => $widget['token'], 'is_active' => true]);
        }

        // The Python side is what actually serves widget.js and answers
        // messages for this token directly to the visitor's browser, so
        // it - not this admin panel - has to be the one enforcing the
        // date window. Push the dates there now rather than waiting for
        // the first settings edit.
        $api->updateWidgetConfig($widget['token'], [
            'company_id' => $validated['company_id'],
            'valid_from' => $validated['valid_from'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        return redirect()->route('widgets.edit', $widget['token'])
            ->with('success', 'Widget created. Configure it and add some FAQs below, then copy the embed script onto your site.');
    }

    public function edit(string $token, WidgetApiService $api)
    {
        $widget = $api->getWidgetConfig($token);
        if (!$widget) {
            abort(404, 'No widget with that token.');
        }

        $faqs = $api->listFaqSources($token);

        $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
        $isRegularEmployee = $employee && $employee->role !== 'ADMIN';

        // Widget row is the source of truth for which company owns this
        // token (see the 2026_09_29_000001 migration); fall back to the
        // legacy companies.widget_token match only for a widget that
        // predates that migration and somehow has no row of its own.
        $widgetRow = \App\Models\Widget::where('token', $token)->first();
        $company = $widgetRow && $widgetRow->company
            ? $widgetRow->company
            : \App\Models\Company::where('widget_token', $token)->first();

        // Explicit ->format('Y-m-d'): valid_from/expiry_date are Carbon
        // instances (cast as dates), and both Blade's {{ }} and a bare
        // (string) cast on Carbon default to "Y-m-d H:i:s" - which an
        // <input type="date"> silently refuses to populate. This is
        // exactly the bug we're fixing, so don't let it back in here.
        $widget['valid_from'] = $widgetRow && $widgetRow->valid_from ? $widgetRow->valid_from->format('Y-m-d') : null;
        $widget['expiry_date'] = $widgetRow && $widgetRow->expiry_date ? $widgetRow->expiry_date->format('Y-m-d') : null;

        $companies = collect();
        $isCompanyUser = !is_null((auth()->user()->company_id ?? auth()->user()->tenant_id));
        if (!$isCompanyUser) {
            $companies = \App\Models\Company::where('bot_usage_type', 'widget')->get();
        }

        return view('widgets.edit', compact('widget', 'faqs', 'token', 'isRegularEmployee', 'company', 'companies'));
    }

    public function updateConfig(Request $request, string $token, WidgetApiService $api)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'contact_email' => ['nullable', 'email', 'max:255', function ($attribute, $value, $fail) {
                if (\App\Models\Company::where('contact_email', $value)->exists()) {
                    $fail('The contact email must be unique and cannot match a company email.');
                }
            }],
            'bot_name' => 'required|string|max:255',
            'welcome_message' => 'required|string|max:500',
            'primary_color' => 'required|string|max:20',
            'button_position' => 'required|in:bottom-left,bottom-right',
            'fallback_message' => 'required|string|max:500',
            'human_handoff_message' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:valid_from',
        ]);
        
        $validated['is_active'] = $request->has('is_active');
        $isCompanyUser = !is_null((auth()->user()->company_id ?? auth()->user()->tenant_id));

        if (!$isCompanyUser) {
            $request->validate(['company_id' => 'nullable|exists:companies,id']);
        }

        // Check if user is a regular employee (not an admin or super admin)
        $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
        $isRegularEmployee = $employee && $employee->role !== 'ADMIN';

        // Widget row is the source of truth for company ownership now
        // (see the 2026_09_29_000001 migration) - fall back to the legacy
        // column match only for a widget that predates it.
        $widgetRow = \App\Models\Widget::where('token', $token)->first();
        $company = $widgetRow && $widgetRow->company
            ? $widgetRow->company
            : \App\Models\Company::where('widget_token', $token)->first();

        // Handle company reassignment by super admin - re-points just
        // THIS widget's own row at a different company. Never touches
        // widget_token on any Company row, so it can't disturb any other
        // widget the old or new company owns.
        if (!$isCompanyUser && $request->has('company_id')) {
            $newCompanyId = $request->input('company_id') ?: null;
            if (!$widgetRow) {
                $widgetRow = \App\Models\Widget::create(['token' => $token, 'is_active' => true]);
            }
            if ($widgetRow->company_id != $newCompanyId) {
                $widgetRow->update(['company_id' => $newCompanyId]);
            }
            $company = $newCompanyId ? \App\Models\Company::find($newCompanyId) : null;
        }

        // Regular employees or Company Users cannot change the dates -
        // fall back to whatever's already saved (formatted plain, same
        // reasoning as in edit() above: valid_from/expiry_date are Carbon
        // instances because of the date cast).
        if ($isRegularEmployee || $isCompanyUser) {
            $validated['valid_from'] = $widgetRow && $widgetRow->valid_from ? $widgetRow->valid_from->format('Y-m-d') : null;
            $validated['expiry_date'] = $widgetRow && $widgetRow->expiry_date ? $widgetRow->expiry_date->format('Y-m-d') : null;
        }

        // Pull the dates out for the local Widget update below, but keep
        // them IN $validated too - the Python service is what actually
        // serves widget.js and answers messages straight to the visitor's
        // browser, so it has to know the window as well, not just this
        // admin panel. Sending null explicitly (not just omitting the
        // key) is what lets a client clear a previously-set date back to
        // "no start date" / "no expiry".
        $validFrom = $validated['valid_from'];
        $expiryDate = $validated['expiry_date'];

        $widgetModel = \App\Models\Widget::where('token', $token)->first();
        $validated['company_id'] = $widgetModel ? $widgetModel->company_id : null;
        $updated = $api->updateWidgetConfig($token, $validated);

        if (!$updated) {
            return back()->withInput()->with('error', 'Could not save the widget config.');
        }

        // This widget's own row, not the whole company - is_active and
        // the date window are per-widget now (matching how the Python
        // side already keys them per-token), so toggling or expiring one
        // of a company's widgets can never take its other widgets down
        // with it.
        if ($widgetRow) {
            $widgetRow->update([
                'is_active' => $validated['is_active'],
                'valid_from' => $validFrom,
                'expiry_date' => $expiryDate,
            ]);
        }

        return back()->with('success', 'Widget settings saved. Live on the next visitor message.');
    }

    public function destroy(string $token, WidgetApiService $api)
    {
        $api->deleteWidget($token);
        // Deactivate just THIS widget's own row, not its company - a
        // company can now own several independent widgets, and deleting
        // one must never take the others offline. Keeps past conversation
        // history intact and viewable in the CRM either way, just stops
        // this widget from accepting new messages (WidgetMessageController
        // already checks is_active before processing anything).
        \App\Models\Widget::where('token', $token)->update(['is_active' => false]);
        return redirect()->route('widgets.index')->with('success', 'Widget permanently deleted, including all of its FAQs and uploaded files.');
    }

    public function storeFaq(Request $request, string $token, WidgetApiService $api)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
            'url' => 'nullable|url|max:255',
            'link_text' => 'nullable|string|max:100',
            'keywords' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $sourceUrl = !empty($validated['url']) ? $validated['url'] : null;
        $attachmentUrl = null;

        // Was calling FaqApiService (the WhatsApp bot's COMPANY-scoped
        // FAQ service, which posts to Python's /company/{id}/faq/*)
        // instead of WidgetApiService (this widget's own TOKEN-scoped
        // service, /widgets/{token}/faq/*) - updateFaq()/editFaq()/
        // destroyFaq() below already used the right one, only this
        // create path didn't. Two compounding problems from that: (1)
        // main.py's /company/{id}/faq/files/attach hands back a URL
        // under /company/{id}/faq/files/... that nothing ever serves
        // (no matching GET route exists for it, unlike the widget's own
        // /widget/{token}/faq/files/... route), so the uploaded file was
        // never actually reachable; and (2) FaqApiService::uploadText()
        // has no attachment_url/link_text parameters at all, so even a
        // working URL would have been silently dropped instead of saved
        // on the FAQ.
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $api->attachFile($token, $file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $attachmentUrl = $attachResult['url'];
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['keywords']))))
            : [];

        $isActive = $request->has('is_active');
        $linkText = !empty($validated['link_text']) ? $validated['link_text'] : null;

        $result = $api->uploadFaqText($token, $validated['question'], $validated['answer'], $sourceUrl, false, $keywordList, $isActive, $linkText, $attachmentUrl);

        if (!$result) {
            return back()->withInput()->with('error', 'Could not save that FAQ - check the bot service is running.');
        }

        return back()->with('success', 'FAQ added.');
    }

    public function destroyFaq(string $token, string $sourceId, WidgetApiService $api)
    {
        $api->deleteFaqSource($token, $sourceId);
        return back()->with('success', 'FAQ removed.');
    }

    public function editFaq(string $token, string $sourceId, WidgetApiService $api)
    {
        $widget = $api->getWidgetConfig($token);
        if (!$widget) {
            return redirect()->route('widgets.index')->with('error', 'Widget not found.');
        }

        $faq = $api->getFaqSource($token, $sourceId);

        if (!$faq) {
            return redirect()->route('widgets.edit', $token)->with('error', 'FAQ not found.');
        }

        return view('widgets.faq-edit', compact('token', 'widget', 'faq', 'sourceId'));
    }

    public function updateFaq(Request $request, string $token, string $sourceId, WidgetApiService $api)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
            'url' => 'nullable|url|max:255',
            'link_text' => 'nullable|string|max:100',
            'keywords' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = $api->getFaqSource($token, $sourceId);

        if (!$faq) {
            return redirect()->route('widgets.edit', $token)->with('error', 'FAQ not found.');
        }

        // The edit form always pre-fills Hyperlink URL with the FAQ's
        // current source_url (see faq-edit.blade.php), so a blank
        // submission here is never "the person didn't touch this field" -
        // it can ONLY happen if they deliberately deleted the pre-filled
        // text. That means blank must actually clear it, not fall back to
        // the old value - falling back (the previous behavior) is exactly
        // why clearing the field and saving appeared to do nothing.
        $submittedUrl = !empty($validated['url']) ? $validated['url'] : null;
        $sourceUrl = $submittedUrl;
        // null here (as opposed to WidgetApiService::updateFaqSource()'s
        // default) is meaningful and gets passed through as-is: it tells
        // the Python side "no new file was attached this time, keep
        // whatever attachment_url this FAQ already had" - see
        // faq_store.py's update_source(). Only set when a NEW file is
        // actually uploaded in this submission.
        $attachmentUrl = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $api->attachFile($token, $file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $attachmentUrl = $attachResult['url'];
            }
        } elseif ($request->input('remove_attachment') == '1') {
            $attachmentUrl = ''; // Empty string signals the python api to clear the attachment
        }

        $keywordList = !empty($validated['keywords'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['keywords']))))
            : [];

        $isActive = $request->has('is_active');
        // Same reasoning as $sourceUrl above - Link Text is also always
        // pre-filled with the FAQ's current label, so a blank submission
        // means it was deliberately cleared, not left untouched. Also,
        // faq_store.py's update_source() discards this on its own if
        // $sourceUrl ends up empty (a label with nothing to link to is
        // never kept), so a stale label can't outlive its link even if
        // both were somehow passed together.
        $linkText = !empty($validated['link_text']) ? $validated['link_text'] : null;

        // Edit in place - same id afterwards. NOT delete-then-recreate:
        // that older pattern assigned a brand new id on every edit,
        // which is exactly what caused "FAQ not found" on a stale edit
        // link right after a successful save, and was a genuine
        // data-loss risk if the re-create step ever failed right after
        // the delete had already gone through.
        $result = $api->updateFaqSource($token, $sourceId, $validated['question'], $validated['answer'], $sourceUrl, false, $keywordList, $isActive, $linkText, $attachmentUrl);

        if (!$result) {
            return redirect()->route('widgets.edit', $token)->with('error', 'Could not update FAQ - check the bot service is running.');
        }

        return redirect()->route('widgets.edit', $token)->with('success', 'FAQ updated.');
    }
}
