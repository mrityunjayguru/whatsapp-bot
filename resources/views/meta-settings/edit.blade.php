@extends('layout.master')

@section('title', 'Meta Ads Integration Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-1 mb-md-0">Meta Ads Integration</h4>
    <p class="text-muted mb-0 mt-1">Configure Facebook / Meta Ads credentials to auto-sync leads from Meta Instant Forms.</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i data-lucide="check-circle" class="icon-sm me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
  </div>
@endif

<div class="row">
  <div class="col-lg-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Credentials</h6>

        <form action="{{ route('meta-settings.update') }}" method="POST" novalidate>
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="app_id" class="form-label">Meta App ID</label>
              <input
                type="text"
                id="app_id"
                name="app_id"
                class="form-control @error('app_id') is-invalid @enderror"
                value="{{ old('app_id', $metaSettings->app_id) }}"
                placeholder="e.g. 1402897375054491"
                autofocus
              >
              @error('app_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="app_secret" class="form-label">Meta App Secret</label>
              <input
                type="text"
                id="app_secret"
                name="app_secret"
                class="form-control @error('app_secret') is-invalid @enderror"
                value="{{ old('app_secret', $metaSettings->app_secret) }}"
                placeholder="e.g. b31ee9091b..."
              >
              @error('app_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="page_id" class="form-label">Facebook Page ID</label>
              <input
                type="text"
                id="page_id"
                name="page_id"
                class="form-control @error('page_id') is-invalid @enderror"
                value="{{ old('page_id', $metaSettings->page_id) }}"
                placeholder="e.g. 1046770308513113"
              >
              @error('page_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="graph_api_version" class="form-label">Graph API Version</label>
              <input
                type="text"
                id="graph_api_version"
                name="graph_api_version"
                class="form-control @error('graph_api_version') is-invalid @enderror"
                value="{{ old('graph_api_version', $metaSettings->graph_api_version) }}"
                placeholder="e.g. v18.0"
              >
              @error('graph_api_version')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mb-3">
            <label for="page_access_token" class="form-label">Page Access Token</label>
            <textarea
              id="page_access_token"
              name="page_access_token"
              rows="2"
              class="form-control @error('page_access_token') is-invalid @enderror"
              placeholder="Paste long-lived page access token..."
            >{{ old('page_access_token', $metaSettings->page_access_token) }}</textarea>
            @error('page_access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="webhook_verify_token" class="form-label">Webhook Verify Token</label>
              <input
                type="text"
                id="webhook_verify_token"
                name="webhook_verify_token"
                class="form-control @error('webhook_verify_token') is-invalid @enderror"
                value="{{ old('webhook_verify_token', $metaSettings->webhook_verify_token) }}"
                placeholder="Any random secure string"
              >
              <div class="form-text">Use this token while creating the Webhook subscription in Meta Developer Dashboard.</div>
              @error('webhook_verify_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="default_created_by" class="form-label">Default Created By User <span class="text-danger">*</span></label>
              <select
                id="default_created_by"
                name="default_created_by"
                class="form-select @error('default_created_by') is-invalid @enderror"
                required
              >
                <option value="">-- Select User --</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}" {{ old('default_created_by', $metaSettings->default_created_by) == $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                    <small class="text-muted">({{ $u->email }})</small>
                  </option>
                @endforeach
              </select>
              <div class="form-text">Meta leads will be stored under this user's name.</div>
              @error('default_created_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="pt-3 border-top">
            <button type="submit" class="btn btn-primary me-2">
              <i data-lucide="save" class="icon-sm me-1"></i> Save Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Webhook Configuration</h6>
        <p class="text-muted">
          Enter the following values in your Meta Developer Dashboard →
          App Dashboard → Webhooks → Page Subscription → New Subscription.
        </p>

        <div class="mb-3">
          <label class="form-label fw-semibold">Callback URL</label>
          <div class="input-group">
            <input type="text" class="form-control bg-light" value="{{ $webhookUrl }}" readonly id="callbackUrl">
            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('callbackUrl', this)">
              <i data-lucide="copy" class="icon-sm"></i>
            </button>
          </div>
          <div class="form-text">Must be <strong>publicly accessible HTTPS URL</strong>.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Verify Token</label>
          <div class="input-group">
            <input type="text" class="form-control bg-light" value="{{ old('webhook_verify_token', $metaSettings->webhook_verify_token) }}" readonly id="verifyToken">
            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('verifyToken', this)">
              <i data-lucide="copy" class="icon-sm"></i>
            </button>
          </div>
        </div>

        <hr class="my-3">

        <h6 class="card-title mb-2">Subscription Fields Required</h6>
        <p class="text-muted mb-2">Subscribe your Page to the following Webhook event:</p>
        <ul class="list-unstyled mb-3">
          <li><span class="badge bg-primary text-white me-2">leadgen</span> Lead Generated (Instant Form submissions)</li>
        </ul>

        <h6 class="card-title mb-2">Setup Steps (Meta Developer Dashboard)</h6>
        <ol class="mb-0 ps-3 text-muted" style="line-height: 1.7;">
          <li>Go to <strong>Meta for Developers → Your App → Webhooks</strong></li>
          <li>Add subscription → Select object <strong>Page</strong></li>
          <li>Paste Callback URL &amp; Verify Token (from above)</li>
          <li>Subscribe to <strong>leadgen</strong> field</li>
          <li>Go to <strong>Graph API Explorer</strong> → Subscribe your Page to this App</li>
          <li>Create an Instant Form in Meta Ads Manager &amp; test submission</li>
        </ol>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  function copyToClipboard(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    el.select();
    el.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(el.value).then(() => {
      const old = btn.innerHTML;
      btn.innerHTML = '<i data-lucide="check" class="icon-sm text-success"></i>';
      lucide.createIcons();
      setTimeout(() => {
        btn.innerHTML = old;
        lucide.createIcons();
      }, 1500);
    });
  }
  lucide.createIcons();
</script>
@endpush
