@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('faqs.index') }}">Faqs</a></li>
    <li class="breadcrumb-item active" aria-current="page">FAQ List</li>
  </ol>
  <div class="d-flex gap-2">
    <a href="{{ route('faq-categories.index') }}" class="btn btn-outline-primary"><i data-lucide="folder" class="icon-sm me-1"></i> FAQ Categories</a>
    <a href="{{ route('faqs.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm me-1"></i> Add FAQ</a>
  </div>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between mb-3 align-items-center flex-wrap gap-2">
            <div>
              <h6 class="card-title mb-1">FAQ List</h6>
              <p class="text-secondary fs-13px mb-0">Manage question and answer pairs synchronized with your AI bot.</p>
            </div>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()"><i data-lucide="refresh-cw" class="icon-sm me-1"></i> Refresh</button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Filters Section --}}
        <form action="{{ route('faqs.index') }}" method="GET" id="faqFilterForm" class="mb-4">
          <div class="row g-2 align-items-center">
            
            {{-- Category Filter --}}
            <div class="col-md-3 col-sm-6">
              <select name="category" class="form-select form-select-sm" onchange="document.getElementById('faqFilterForm').submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                    {{ $cat }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Status Filter --}}
            <div class="col-md-2 col-sm-6">
              <select name="status" class="form-select form-select-sm" onchange="document.getElementById('faqFilterForm').submit()">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>

            {{-- Search Input --}}
            <div class="col-md-4 col-sm-8 ms-auto">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent"><i data-lucide="search" class="icon-sm text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search question, answer, keywords..." value="{{ request('search') }}" onkeypress="if(event.key === 'Enter') { document.getElementById('faqFilterForm').submit(); return false; }">
              </div>
            </div>

            {{-- Clear Filters Button --}}
            @if(request()->hasAny(['category', 'status', 'search']))
              <div class="col-auto">
                <a href="{{ route('faqs.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                  <i data-lucide="rotate-ccw" class="icon-sm me-1"></i> Reset
                </a>
              </div>
            @endif

          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr class="bg-light">
                <th>FAQ ID</th>
                <th>QUESTION</th>
                <th>CATEGORY</th>
                <th>KEYWORDS</th>
                <th>ANSWER PREVIEW</th>
                <th>ATTACHMENT</th>
                <!-- <th>URL</th> -->
                <th>STATUS</th>
                <th>CREATED BY</th>
                <th>CREATED AT</th>
                <th>UPDATED AT</th>
                <th>ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($faqs as $faq)
              <tr>
                <td>{{ $faq->faq_hash_id }}</td>
                <td class="fw-bold">{{ $faq->question }}</td>
                <td><span class="badge bg-light text-dark border">{{ $faq->category ?? 'General' }}</span></td>
                <td>
                    @php
                        $kwRaw = $faq->keywords ?? '';
                        $kwList = array_filter(array_map('trim', explode(',', $kwRaw)));
                        $kwCount = count($kwList);
                    @endphp
                    @if($kwCount > 0)
                        @if($kwCount <= 3)
                            <span>{{ implode(', ', $kwList) }}</span>
                        @else
                            <span data-bs-toggle="tooltip" data-bs-html="true" title="{{ implode('<br>', array_map('e', $kwList)) }}" style="cursor: help;">
                                {{ implode(', ', array_slice($kwList, 0, 3)) }}...
                            </span>
                        @endif
                    @else
                        --
                    @endif
                </td>
                <td>{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 30) }}</td>
                <td>
                    @if($faq->attachment)
                        <a href="{{ $faq->attachment }}" target="_blank" class="text-primary text-decoration-underline"><i data-lucide="paperclip" class="icon-sm"></i> {{ basename($faq->attachment) }}</a>
                    @else
                        --
                    @endif
                </td>
                <!-- <td>
                    @if($faq->url)
                        <a href="{{ $faq->url }}" target="_blank" class="text-primary text-decoration-underline">Link</a>
                    @else
                        --
                    @endif
                </td> -->
                <td>
                    <span class="badge {{ $faq->is_active ? 'bg-success' : 'bg-secondary' }} text-white" style="opacity: 0.8;">{{ $faq->is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td>{{ $faq->creator->name ?? '--' }}</td>
                <td>{{ $faq->created_at->format('d M Y, h:i A') }}</td>
                <td>{{ $faq->updated_at->format('d M Y, h:i A') }}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-link p-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-lucide="more-horizontal" class="icon-sm"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('faqs.edit', $faq->id) }}"><i data-lucide="edit" class="icon-sm me-2 text-warning"></i> Edit FAQ</a></li>
                            <li>
                                <form action="{{ route('faqs.toggle-status', $faq->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button" class="dropdown-item btn-toggle-status" 
                                            data-name="{{ $faq->question }}" 
                                            data-action="{{ $faq->is_active ? 'deactivate' : 'activate' }}">
                                        <i data-lucide="power" class="icon-sm me-2 {{ $faq->is_active ? 'text-danger' : 'text-success' }}"></i> {{ $faq->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('faqs.destroy', $faq->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="dropdown-item text-danger btn-delete-faq" data-name="{{ $faq->question }}">
                                        <i data-lucide="trash" class="icon-sm me-2 text-danger"></i> Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="14" class="text-center text-muted py-4">No FAQs found. Click "Add FAQ" to create one.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        
      </div>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // SweetAlert for FAQ status toggle
  document.querySelectorAll('.btn-toggle-status').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const form = this.closest('form');
      const faqName = this.getAttribute('data-name') || 'this FAQ';
      const action = this.getAttribute('data-action') || 'toggle';
      const isDeactivating = (action === 'deactivate');

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Update FAQ Status?',
          html: `Are you sure you want to <strong>${action}</strong> this FAQ?<br><small class="text-muted text-truncate d-inline-block" style="max-width: 350px;">"${faqName}"</small>`,
          icon: isDeactivating ? 'warning' : 'question',
          showCancelButton: true,
          confirmButtonText: isDeactivating ? 'Yes, Deactivate' : 'Yes, Activate',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: isDeactivating ? 'btn btn-warning me-2' : 'btn btn-success me-2',
            cancelButton: 'btn btn-secondary'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      } else {
        if (confirm(`Are you sure you want to ${action} this FAQ?`)) {
          form.submit();
        }
      }
    });
  });

  // SweetAlert for FAQ delete
  document.querySelectorAll('.btn-delete-faq').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const form = this.closest('form');
      const faqName = this.getAttribute('data-name') || 'this FAQ';

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Delete FAQ?',
          html: `Are you sure you want to permanently delete this FAQ?<br><small class="text-danger text-truncate d-inline-block" style="max-width: 350px;">"${faqName}"</small>`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, Delete',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      } else {
        if (confirm('Are you sure you want to delete this FAQ?')) {
          form.submit();
        }
      }
    });
  });
});
</script>
@endpush