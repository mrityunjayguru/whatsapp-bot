@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('faqs.index') }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to FAQs</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('faqs.index') }}">Faqs</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
  </ol>
</nav>

@if ($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('faqs.update', $faq->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-5">
            <!-- Section 1 -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-4 border-bottom pb-2">SECTION 1 : FAQ DETAILS</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Question <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="question" value="{{ old('question', $faq->question) }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="General" {{ $faq->category === 'General' ? 'selected' : '' }}>General</option>
                                <option value="Billing" {{ $faq->category === 'Billing' ? 'selected' : '' }}>Billing</option>
                                <option value="Technical" {{ $faq->category === 'Technical' ? 'selected' : '' }}>Technical</option>
                                <option value="Sales" {{ $faq->category === 'Sales' ? 'selected' : '' }}>Sales</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Keywords (comma-separated)</label>
                            <input type="text" class="form-control" name="keywords" value="{{ old('keywords', $faq->keywords) }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Match Type</label>
                            <select class="form-select" name="match_type">
                                <option value="Exact Match" {{ $faq->match_type === 'Exact Match' ? 'selected' : '' }}>Exact Match</option>
                                <option value="AI Semantic" {{ $faq->match_type === 'AI Semantic' ? 'selected' : '' }}>AI Semantic</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="Low" {{ $faq->priority === 'Low' ? 'selected' : '' }}>Low</option>
                                <option value="Medium" {{ $faq->priority === 'Medium' ? 'selected' : '' }}>Medium</option>
                                <option value="High" {{ $faq->priority === 'High' ? 'selected' : '' }}>High</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3 -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-4 border-bottom pb-2">SECTION 3 : SETTINGS</h6>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <p class="mb-0 fw-bold">FAQ Status</p>
                            <small class="text-muted">Set this FAQ to Active or Inactive</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input" id="isActiveSwitch" name="is_active" {{ $faq->is_active ? 'checked' : '' }} style="width: 40px; height: 20px;">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-2">
                        <a href="{{ route('faqs.index') }}" class="btn btn-outline-secondary w-50">Cancel</a>
                        <button type="submit" class="btn btn-primary w-50">Update FAQ</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-7">
            <!-- Section 2 -->
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title text-muted mb-4 border-bottom pb-2">SECTION 2 : CHATBOT RESPONSE</h6>
                    
                    <div class="mb-3 flex-grow-1 d-flex flex-column">
                        <label class="form-label">Answer <span class="text-danger">*</span></label>
                        <textarea class="form-control flex-grow-1" id="answer" name="answer" rows="12">{{ old('answer', $faq->answer) }}</textarea>
                    </div>

                    <input type="hidden" name="remove_attachment" id="removeAttachment" value="0">

                    <div class="row align-items-end mb-3">
                        <div class="col-md-8">
                            <label class="form-label"><i data-lucide="paperclip" class="icon-sm text-muted me-1"></i> Attachment</label>
                            <input type="file" class="form-control" name="attachment" id="attachment">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-danger w-100" id="removeAttachmentBtn" style="display: none;">
                                <i data-lucide="x" class="icon-sm me-1"></i> Remove
                            </button>
                        </div>
                    </div>

                    <div id="currentAttachmentInfo" class="mb-3" @if(!$faq->attachment) style="display: none;" @endif>
                        @if($faq->attachment)
                            <small class="text-muted d-block">Current: <a href="{{ $faq->attachment }}" target="_blank">View File</a></small>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-1" id="removeCurrentAttachmentBtn">
                                <i data-lucide="trash-2" class="icon-sm me-1"></i> Remove Current Attachment
                            </button>
                        @endif
                    </div>

                    <div id="selectedFileInfo" class="mb-3" style="display: none;">
                        <small class="text-muted">Selected: <span id="selectedFileName"></span></small>
                    </div>

                    <!-- <div class="mb-3">
                        <label class="form-label"><i data-lucide="link" class="icon-sm text-muted me-1"></i> URL</label>
                        <input type="url" class="form-control" name="url" value="{{ old('url', $faq->url) }}">
                    </div> -->
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('custom-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
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
        document.querySelector('form').addEventListener('submit', function(e) {
            tinymce.triggerSave();
            var val = document.getElementById('answer').value.trim();
            if (!val || val === '<p></p>' || val === '<p><br></p>') {
                e.preventDefault();
                alert('Please enter an answer.');
                tinymce.get('answer').focus();
            }
        });

        var attachmentInput = document.getElementById('attachment');
        var removeBtn = document.getElementById('removeAttachmentBtn');
        var selectedFileInfo = document.getElementById('selectedFileInfo');
        var selectedFileName = document.getElementById('selectedFileName');
        var currentAttachmentInfo = document.getElementById('currentAttachmentInfo');
        var removeCurrentBtn = document.getElementById('removeCurrentAttachmentBtn');
        var removeAttachmentHidden = document.getElementById('removeAttachment');

        function updateRemoveBtnVisibility() {
            if (attachmentInput.files && attachmentInput.files.length > 0) {
                selectedFileName.textContent = attachmentInput.files[0].name;
                selectedFileInfo.style.display = 'block';
                removeBtn.style.display = 'inline-flex';
                removeBtn.classList.add('align-items-center', 'justify-content-center');
            } else {
                selectedFileInfo.style.display = 'none';
                removeBtn.style.display = 'none';
            }
        }

        attachmentInput.addEventListener('change', function() {
            updateRemoveBtnVisibility();
        });

        removeBtn.addEventListener('click', function() {
            attachmentInput.value = '';
            updateRemoveBtnVisibility();
        });

        if (removeCurrentBtn) {
            removeCurrentBtn.addEventListener('click', function() {
                removeAttachmentHidden.value = '1';
                currentAttachmentInfo.style.display = 'none';
                attachmentInput.value = '';
                updateRemoveBtnVisibility();
            });
        }
    });
</script>
@endpush
