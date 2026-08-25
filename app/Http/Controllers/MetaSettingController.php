<?php

namespace App\Http\Controllers;

use App\Models\MetaSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetaSettingController extends Controller
{
    public function edit(Request $request): View
    {
        $user     = $request->user();
        $ownerId  = $user->companyOwnerId();

        $company = \App\Models\Company::where('user_id', $ownerId)->first();
        abort_if(!$company, 404, 'Company profile not found.');

        $metaSettings = MetaSetting::firstOrNew([
            'company_id' => $company->id,
        ], [
            'app_id'            => (string) config('services.meta.app_id'),
            'app_secret'        => (string) config('services.meta.app_secret'),
            'page_id'           => (string) config('services.meta.page_id'),
            'page_access_token' => (string) config('services.meta.page_access_token'),
            'webhook_verify_token' => (string) config('services.meta.webhook_verify'),
            'graph_api_version' => (string) config('services.meta.graph_api_version'),
            'default_created_by' => (int) config('services.meta.default_user_id'),
        ]);

        $companyUserIds = $user->companyUserIds();
        $users = User::whereIn('id', $companyUserIds)->orderBy('name')->get();

        $webhookUrl = route('meta.webhook');

        return view('meta-settings.edit', compact('metaSettings', 'users', 'company', 'webhookUrl'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user     = $request->user();
        $ownerId  = $user->companyOwnerId();

        $company = \App\Models\Company::where('user_id', $ownerId)->first();
        abort_if(!$company, 404, 'Company profile not found.');

        $companyUserIds = $user->companyUserIds();

        $request->validate([
            'app_id'              => ['nullable', 'string', 'max:255'],
            'app_secret'          => ['nullable', 'string', 'max:255'],
            'page_id'             => ['nullable', 'string', 'max:255'],
            'page_access_token'   => ['nullable', 'string', 'max:2000'],
            'webhook_verify_token'=> ['nullable', 'string', 'max:255'],
            'graph_api_version'   => ['nullable', 'string', 'max:20'],
            'default_created_by'  => ['nullable', 'exists:users,id'],
        ], [], [
            'default_created_by' => 'Default Created By User',
        ]);

        if ($request->filled('default_created_by')
            && !in_array((int) $request->default_created_by, $companyUserIds, true)) {
            return back()->withInput()->withErrors(['default_created_by' => 'Invalid user selection.']);
        }

        $settings = MetaSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'app_id'              => $request->app_id,
                'app_secret'          => $request->app_secret,
                'page_id'             => $request->page_id,
                'page_access_token'   => $request->page_access_token,
                'webhook_verify_token'=> $request->webhook_verify_token ?: null,
                'graph_api_version'   => $request->graph_api_version ?: 'v18.0',
                'default_created_by'  => $request->default_created_by,
            ]
        );

        return redirect()->route('meta-settings.edit')
            ->with('success', 'Meta Ads settings saved successfully.');
    }
}
