<?php

namespace App\Http\Controllers;

use App\Services\WidgetApiService;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function index(WidgetApiService $api)
    {
        $widgets = $api->listWidgets();
        
        $companies = \App\Models\Company::whereNotNull('widget_token')->pluck('name', 'widget_token');

        foreach ($widgets as &$widget) {
            $widget['company_name'] = $companies[$widget['token']] ?? '--';
        }
        
        if (!is_null(auth()->user()->company_id)) {
            $token = auth()->user()->company->widget_token;
            $widgets = collect($widgets)->filter(fn($w) => $w['token'] === $token)->values()->all();
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
            'contact_email' => 'nullable|email|max:255',
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $widget = $api->createWidget($validated['site_name'], $validated['contact_email'] ?? null);

        if (!$widget) {
            return back()->withInput()->with('error', 'Could not create the widget - check the bot service is running.');
        }

        $company = \App\Models\Company::findOrFail($validated['company_id']);
        $company->update([
            'widget_token' => $widget['token'],
            'is_active' => true,
            'valid_from' => $validated['valid_from'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        // The Python side is what actually serves widget.js and answers
        // messages for this token directly to the visitor's browser, so
        // it - not this admin panel - has to be the one enforcing the
        // date window. Push the dates there now rather than waiting for
        // the first settings edit.
        $api->updateWidgetConfig($widget['token'], [
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

        $company = \App\Models\Company::where('widget_token', $token)->first();
        // Explicit ->format('Y-m-d'): $company->valid_from is a Carbon
        // instance (it's cast as a date), and both Blade's {{ }} and a
        // bare (string) cast on Carbon default to "Y-m-d H:i:s" - which
        // an <input type="date"> silently refuses to populate. This is
        // exactly the bug we're fixing, so don't let it back in here.
        $widget['valid_from'] = $company && $company->valid_from ? $company->valid_from->format('Y-m-d') : null;
        $widget['expiry_date'] = $company && $company->expiry_date ? $company->expiry_date->format('Y-m-d') : null;

        $companies = collect();
        $isCompanyUser = !is_null(auth()->user()->company_id);
        if (!$isCompanyUser) {
            $companies = \App\Models\Company::where('bot_usage_type', 'widget')->get();
        }

        return view('widgets.edit', compact('widget', 'faqs', 'token', 'isRegularEmployee', 'company', 'companies'));
    }

    public function updateConfig(Request $request, string $token, WidgetApiService $api)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
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
        $isCompanyUser = !is_null(auth()->user()->company_id);

        if (!$isCompanyUser) {
            $request->validate(['company_id' => 'nullable|exists:companies,id']);
        }

        // Check if user is a regular employee (not an admin or super admin)
        $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
        $isRegularEmployee = $employee && $employee->role !== 'ADMIN';

        $company = \App\Models\Company::where('widget_token', $token)->first();

        // Handle company reassignment by super admin
        if (!$isCompanyUser && $request->has('company_id')) {
            $newCompanyId = $request->input('company_id');
            if (!$company || $company->id != $newCompanyId) {
                if ($company) {
                    $company->update(['widget_token' => null]);
                }
                if ($newCompanyId) {
                    $company = \App\Models\Company::find($newCompanyId);
                    if ($company) {
                        $company->update(['widget_token' => $token]);
                    }
                } else {
                    $company = null;
                }
            }
        }

        // Regular employees or Company Users cannot change the dates -
        // fall back to whatever's already saved (formatted plain, same
        // reasoning as in edit() above: $company->valid_from is a Carbon
        // instance because of the date cast).
        if ($isRegularEmployee || $isCompanyUser) {
            $validated['valid_from'] = $company && $company->valid_from ? $company->valid_from->format('Y-m-d') : null;
            $validated['expiry_date'] = $company && $company->expiry_date ? $company->expiry_date->format('Y-m-d') : null;
        }

        // Pull the dates out for the local Company update below, but keep
        // them IN $validated too - the Python service is what actually
        // serves widget.js and answers messages straight to the visitor's
        // browser, so it has to know the window as well, not just this
        // admin panel. Sending null explicitly (not just omitting the
        // key) is what lets a client clear a previously-set date back to
        // "no start date" / "no expiry".
        $validFrom = $validated['valid_from'];
        $expiryDate = $validated['expiry_date'];

        $updated = $api->updateWidgetConfig($token, $validated);

        if (!$updated) {
            return back()->withInput()->with('error', 'Could not save the widget config.');
        }

        if ($company) {
            $company->update([
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
        // Deactivate rather than delete the Company row - keeps past
        // conversation history intact and viewable in the CRM, just
        // stops it from accepting new messages (WidgetMessageController
        // already checks is_active before processing anything).
        \App\Models\Company::where('widget_token', $token)->update(['is_active' => false]);
        return redirect()->route('widgets.index')->with('success', 'Widget permanently deleted, including all of its FAQs and uploaded files.');
    }

    public function storeFaq(Request $request, string $token, WidgetApiService $api)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
            'url' => 'nullable|url|max:255',
            'keywords' => 'nullable|string|max:255',
        ]);

        // Same pattern as FaqController: an attached file (stored, not
        // indexed) wins over the plain reference URL field - only one
        // ever becomes source_url, and exactly ONE Python source gets
        // created per FAQ.
        $sourceUrl = $validated['url'] ?? null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $api->attachFile($token, $file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $sourceUrl = $attachResult['url'];
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['keywords']))))
            : [];

        $result = $api->uploadFaqText($token, $validated['question'], $validated['answer'], $sourceUrl, false, $keywordList);

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
            'keywords' => 'nullable|string|max:255',
        ]);

        $faq = $api->getFaqSource($token, $sourceId);

        if (!$faq) {
            return redirect()->route('widgets.edit', $token)->with('error', 'FAQ not found.');
        }

        $sourceUrl = $validated['url'] ?? $faq['source_url'] ?? null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $api->attachFile($token, $file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $sourceUrl = $attachResult['url'];
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['keywords']))))
            : [];

        // Edit in place - same id afterwards. NOT delete-then-recreate:
        // that older pattern assigned a brand new id on every edit,
        // which is exactly what caused "FAQ not found" on a stale edit
        // link right after a successful save, and was a genuine
        // data-loss risk if the re-create step ever failed right after
        // the delete had already gone through.
        $result = $api->updateFaqSource($token, $sourceId, $validated['question'], $validated['answer'], $sourceUrl, false, $keywordList);

        if (!$result) {
            return redirect()->route('widgets.edit', $token)->with('error', 'Could not update FAQ - check the bot service is running.');
        }

        return redirect()->route('widgets.edit', $token)->with('success', 'FAQ updated.');
    }
}