<?php

namespace App\Http\Controllers;

use App\Services\WidgetApiService;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function index(WidgetApiService $api)
    {
        $widgets = $api->listWidgets();
        return view('widgets.index', compact('widgets'));
    }

    public function create()
    {
        return view('widgets.create');
    }

    public function store(Request $request, WidgetApiService $api)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
        ]);

        $widget = $api->createWidget($validated['site_name'], $validated['contact_email'] ?? null);

        if (!$widget) {
            return back()->withInput()->with('error', 'Could not create the widget - check the bot service is running.');
        }

        // The widget only exists in Python's own storage at this point.
        // WidgetMessageController (the endpoint every visitor message
        // actually goes through) requires a matching Company row here
        // in Laravel before it'll process anything for this token - this
        // used to be a manual tinker step per widget, easy to forget,
        // which meant a brand new widget silently failed every message
        // until someone remembered to run it by hand. Creating it here
        // means every widget works immediately, with nothing else to do.
        \App\Models\Company::firstOrCreate(
            ['widget_token' => $widget['token']],
            [
                'name' => $validated['site_name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'is_active' => true,
            ]
        );

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
        $widget['expiry_date'] = $company ? $company->expiry_date : null;

        return view('widgets.edit', compact('widget', 'faqs', 'token', 'isRegularEmployee'));
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
            'expiry_date' => 'nullable|date',
        ]);
        
        $validated['is_active'] = $request->has('is_active');

        // Check if user is a regular employee (not an admin or super admin)
        $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
        $isRegularEmployee = $employee && $employee->role !== 'ADMIN';

        $company = \App\Models\Company::where('widget_token', $token)->first();

        // Regular employees cannot change the expiry date
        if ($isRegularEmployee) {
            $validated['expiry_date'] = $company ? $company->expiry_date : null;
        }

        // We do NOT send expiry_date to Python anymore. We store it locally.
        $expiryDate = $validated['expiry_date'];
        unset($validated['expiry_date']);

        $updated = $api->updateWidgetConfig($token, $validated);

        if (!$updated) {
            return back()->withInput()->with('error', 'Could not save the widget config.');
        }

        if ($company) {
            $company->update([
                'name' => $validated['site_name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'is_active' => $validated['is_active'],
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
