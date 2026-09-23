@extends('layout.master')

@php
    $publicApiBase = rtrim(config('app.url'), '/') . '/pybot';
    $embedSnippet = '<script src="' . $publicApiBase . '/widget/widget.js?token=' . $token . '" async></script>';
    $previewUrl = $publicApiBase . '/widget/preview?token=' . $token;
@endphp

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('widgets.index') }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to Widgets</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('widgets.index') }}">Widgets</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $widget['site_name'] }}</li>
  </ol>
</nav>

@if (session('success'))
  <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger mb-4">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger mb-4">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<!-- Embed snippet -->
<div class="card mb-3">
  <div class="card-body">
    <h6 class="card-title text-muted mb-3 border-bottom pb-2">EMBED ON YOUR SITE</h6>
    <p class="text-muted small mb-2">Paste this before <code>&lt;/body&gt;</code> on any page:</p>
    <div class="d-flex gap-2 align-items-start">
      <textarea class="form-control font-monospace small" id="embedSnippet" rows="1" readonly style="resize:none;">{{ $embedSnippet }}</textarea>
      <button type="button" class="btn btn-outline-secondary flex-shrink-0" onclick="navigator.clipboard.writeText(document.getElementById('embedSnippet').value)">
        <i data-lucide="copy" class="icon-sm"></i> Copy
      </button>
    </div>
    <a href="{{ $previewUrl }}" target="_blank" class="btn btn-sm btn-link ps-0 mt-2">
      <i data-lucide="external-link" class="icon-sm me-1"></i> Open test page
    </a>
  </div>
</div>

<div class="row">
  <!-- Left: appearance/behavior config -->
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">WIDGET SETTINGS</h6>

        <form action="{{ route('widgets.config.update', $token) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label">Site name</label>
            <input type="text" class="form-control" name="site_name" value="{{ old('site_name', $widget['site_name']) }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Client contact email</label>
            <input type="email" class="form-control" name="contact_email" value="{{ old('contact_email', $widget['contact_email'] ?? '') }}" placeholder="client@example.com">
          </div>

          <div class="mb-3">
            <label class="form-label">Bot name</label>
            <input type="text" class="form-control" name="bot_name" value="{{ old('bot_name', $widget['bot_name']) }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Welcome message</label>
            <textarea class="form-control" name="welcome_message" rows="2" required>{{ old('welcome_message', $widget['welcome_message']) }}</textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Accent color</label>
              <input type="color" class="form-control form-control-color w-100" name="primary_color" value="{{ old('primary_color', $widget['primary_color']) }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Button position</label>
              <select class="form-select" name="button_position">
                <option value="bottom-right" @selected(old('button_position', $widget['button_position']) === 'bottom-right')>Bottom right</option>
                <option value="bottom-left" @selected(old('button_position', $widget['button_position']) === 'bottom-left')>Bottom left</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Fallback message <span class="text-muted small">(shown when nothing matches)</span></label>
            <textarea class="form-control" name="fallback_message" rows="2" required>{{ old('fallback_message', $widget['fallback_message']) }}</textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Expiry Date <span class="text-muted small">(Leave empty for no expiry)</span></label>
            <input type="date" class="form-control" name="expiry_date" value="{{ old('expiry_date', $widget['expiry_date'] ?? '') }}" {{ $isRegularEmployee ? 'readonly' : '' }}>
            @if($isRegularEmployee)
              <small class="form-text text-muted">Only Company Admins can change the expiry date.</small>
            @endif
          </div>

          <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded mb-4">
            <div>
              <p class="mb-0 fw-bold">Widget Status</p>
              <small class="text-muted">Turn off to take it offline without deleting it</small>
            </div>
            <div class="form-check form-switch mb-0">
              <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $widget['is_active'])) style="width: 40px; height: 20px;">
            </div>
          </div>

          <div class="d-flex justify-content-end border-top pt-3">
            <button type="submit" class="btn btn-primary">Save Settings</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3 border-danger">
      <div class="card-body">
        <h6 class="card-title text-danger mb-3 border-bottom pb-2">DANGER ZONE</h6>
        <p class="text-muted small mb-3">Permanently deletes this widget's config, every FAQ, and every uploaded file. The embed script on the client's site will stop working immediately. This cannot be undone.</p>
        <form action="{{ route('widgets.destroy', $token) }}" method="POST"
              onsubmit="return confirm('Permanently delete \'{{ $widget['site_name'] }}\' and ALL of its FAQs/files? This cannot be undone.');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger w-100">Delete This Widget Permanently</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Right: FAQ knowledge base -->
  <div class="col-md-7">
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">ADD A FAQ</h6>

        <form action="{{ route('widgets.faqs.store', $token) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label class="form-label">Question <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="question" placeholder="Enter question..." required>
          </div>
          <div class="mb-3">
            <label class="form-label">Answer <span class="text-danger">*</span></label>
            <textarea class="form-control" name="answer" rows="4" placeholder="Write the complete answer here..." required></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><i data-lucide="paperclip" class="icon-sm text-muted me-1"></i> Attachment</label>
              <input type="file" class="form-control" name="attachment">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Keywords <span class="text-muted small">(comma separated)</span></label>
              <input type="text" class="form-control" name="keywords" placeholder="e.g. pricing, cost">
            </div>
          </div>
          <div class="d-flex justify-content-end border-top pt-3">
            <button type="submit" class="btn btn-primary">Add FAQ</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">KNOWLEDGE BASE ({{ count($faqs) }})</h6>

        @if (empty($faqs))
          <p class="text-muted mb-0">No FAQs yet - add one above.</p>
        @else
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Question</th>
                  <th>Keywords</th>
                  <th>Attachment</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($faqs as $faq)
                  <tr>
                    <td>{{ $faq['name'] }}</td>
                    <td>
                      @if (!empty($faq['keywords']))
                        <span class="text-muted small">{{ is_array($faq['keywords']) ? implode(', ', $faq['keywords']) : $faq['keywords'] }}</span>
                      @else
                        --
                      @endif
                    </td>
                    <td>
                      @if (!empty($faq['source_url']))
                        <a href="{{ $faq['source_url'] }}" target="_blank" class="small">
                          <i data-lucide="paperclip" class="icon-sm"></i> Link
                        </a>
                      @else
                        --
                      @endif
                    </td>
                    <td class="text-end">
                      <a href="{{ route('widgets.faqs.edit', [$token, $faq['id']]) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                      <form action="{{ route('widgets.faqs.destroy', [$token, $faq['id']]) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Remove this FAQ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
