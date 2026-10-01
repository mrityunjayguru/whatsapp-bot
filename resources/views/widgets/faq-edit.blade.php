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
            <textarea class="form-control" name="answer" id="answer" rows="6">{{ old('answer', $faq['text'] ?? '') }}</textarea>
            @if(empty($faq['text']))
              <div class="form-text text-danger">Could not load the existing answer - check the bot service is running before saving, or you'll overwrite it with an empty one.</div>
            @endif
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><i data-lucide="paperclip" class="icon-sm text-muted me-1"></i> Replace Attachment</label>
              <div class="input-group">
                <input type="file" class="form-control" name="attachment" id="faqAttachmentInput">
                <button class="btn btn-outline-secondary" type="button" id="btnClearAttachmentInput" title="Clear selected file" style="display: none; padding: 0.375rem 0.75rem;">
                  <i data-lucide="x" class="icon-sm"></i>
                </button>
              </div>
              @php
                // attachment_url (the separately-attached file) - not
                // source_url, which is now the manually-typed Hyperlink
                // URL field below. Falls back to source_url only for a
                // FAQ that predates that split.
                $currentAttachment = $faq['attachment_url'] ?? $faq['source_url'] ?? null;
              @endphp
              @if(!empty($currentAttachment))
                <div id="currentAttachmentWrapper" class="mt-2 d-flex align-items-center">
                  <small class="form-text text-muted mb-0 me-2">Current: <a href="{{ $currentAttachment }}" target="_blank">View File</a></small>
                  <button type="button" class="btn btn-xs btn-danger" id="btnRemoveExistingAttachment" title="Remove current attachment" style="padding: 2px 6px;">
                    <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                  </button>
                </div>
                <input type="hidden" name="remove_attachment" id="removeAttachmentFlag" value="0">
              @endif
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Keywords <span class="text-muted small">(comma separated)</span></label>
              <input type="text" class="form-control" name="keywords" value="{{ old('keywords', isset($faq['keywords']) && is_array($faq['keywords']) ? implode(', ', $faq['keywords']) : '') }}">
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

@push('custom-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const attachInput = document.getElementById('faqAttachmentInput');
        const btnClearAttach = document.getElementById('btnClearAttachmentInput');
        const btnRemoveExisting = document.getElementById('btnRemoveExistingAttachment');
        const wrapperExisting = document.getElementById('currentAttachmentWrapper');
        const flagRemove = document.getElementById('removeAttachmentFlag');

        if (attachInput && btnClearAttach) {
            attachInput.addEventListener('change', function() {
                btnClearAttach.style.display = this.value ? 'block' : 'none';
            });
            btnClearAttach.addEventListener('click', function() {
                attachInput.value = '';
                btnClearAttach.style.display = 'none';
            });
        }

        if (btnRemoveExisting && wrapperExisting && flagRemove) {
            btnRemoveExisting.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove the current attachment?')) {
                    wrapperExisting.style.display = 'none';
                    flagRemove.value = '1';
                }
            });
        }

        tinymce.init({
            selector: '#answer',
            menubar: false,
            plugins: 'link lists',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link',
            promotion: false,
            branding: false,
            setup: function(editor) {
                editor.on('submit', function() {
                    editor.save();
                });
            }
        });

        // Sync TinyMCE content to textarea before form submit and validate
        document.querySelector('form').addEventListener('submit', function(e) {
            tinymce.triggerSave();
            var val = document.getElementById('answer').value.trim();
            if (!val || val === '<p></p>' || val === '<p><br></p>') {
                e.preventDefault();
                alert('Please enter an answer.');
                tinymce.get('answer').focus();
            }
        });
    });
</script>
@endpush
