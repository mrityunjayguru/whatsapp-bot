@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('widgets.edit', $token) }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to Widget</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('widgets.index') }}">Widgets</a></li>
    <li class="breadcrumb-item"><a href="{{ route('widgets.edit', $token) }}">{{ $widget['site_name'] }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit FAQ</li>
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

<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">EDIT FAQ</h6>

        <form action="{{ route('widgets.faqs.update', [$token, $sourceId]) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <div class="mb-3">
            <label class="form-label">Question <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="question" value="{{ old('question', $faq['source_name'] ?? '') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Answer <span class="text-danger">*</span></label>
            <textarea class="form-control" name="answer" rows="6" required>{{ old('answer', $faq['text'] ?? '') }}</textarea>
            @if(empty($faq['text']))
              <div class="form-text text-danger">Could not load the existing answer - check the bot service is running before saving, or you'll overwrite it with an empty one.</div>
            @endif
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><i data-lucide="paperclip" class="icon-sm text-muted me-1"></i> Replace Attachment</label>
              <input type="file" class="form-control" name="attachment">
              @if(!empty($faq['source_url']))
                <small class="form-text text-muted">Current: <a href="{{ $faq['source_url'] }}" target="_blank">View File</a></small>
              @endif
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Keywords <span class="text-muted small">(comma separated)</span></label>
              <input type="text" class="form-control" name="keywords" value="{{ old('keywords', isset($faq['keywords']) && is_array($faq['keywords']) ? implode(', ', $faq['keywords']) : '') }}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><i data-lucide="link" class="icon-sm text-muted me-1"></i> Hyperlink URL</label>
              <input type="url" class="form-control" name="url" value="{{ old('url', $faq['source_url'] ?? '') }}" placeholder="https://example.com/page">
              <div class="form-text">Paste a link here to turn it into a clickable hyperlink in this FAQ's answer.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Link Text <span class="text-muted small">(shown to visitors)</span></label>
              <input type="text" class="form-control" name="link_text" value="{{ old('link_text', $faq['link_text'] ?? '') }}" placeholder="e.g. Click here, View pricing">
              <div class="form-text">Leave blank to show the raw URL as the link.</div>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input type="checkbox" class="form-check-input" name="is_active" id="faqStatusEdit" value="1" @checked(!isset($faq['is_active']) || $faq['is_active'])>
              <label class="form-check-label" for="faqStatusEdit">Active</label>
            </div>
          </div>
          <div class="d-flex justify-content-end border-top pt-3">
            <a href="{{ route('widgets.edit', $token) }}" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Update FAQ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
