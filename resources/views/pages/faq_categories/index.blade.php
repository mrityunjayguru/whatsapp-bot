@extends('layout.master')

@section('title', 'FAQ Categories')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('faqs.index') }}">Faqs</a></li>
    <li class="breadcrumb-item active" aria-current="page">FAQ Categories</li>
  </ol>
  <div class="d-flex gap-2">
    <a href="{{ route('faqs.index') }}" class="btn btn-outline-secondary"><i data-lucide="help-circle" class="icon-sm me-1"></i> Back to FAQs</a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
      <i data-lucide="plus-circle" class="icon-sm me-1"></i> Add Category
    </button>
  </div>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">FAQ Categories</h6>
            <p class="text-secondary fs-13px mb-0">Organize and group your FAQs into custom categories for chatbot answers.</p>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i data-lucide="alert-circle" class="icon-sm me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        {{-- Filter Form --}}
        <form action="{{ route('faq-categories.index') }}" method="GET" id="categoryFilterForm" class="mb-4">
          <div class="row g-2 align-items-center">
            
            <div class="col-md-2 col-sm-6">
              <select name="status" class="form-select form-select-sm" onchange="document.getElementById('categoryFilterForm').submit()">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>

            <div class="col-md-3 col-sm-6">
              <input type="date" name="created_date" class="form-control form-control-sm" value="{{ request('created_date') }}" onchange="document.getElementById('categoryFilterForm').submit()">
            </div>

            <div class="col-md-4 col-sm-8 ms-auto">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent"><i data-lucide="search" class="icon-sm text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search Category..." value="{{ request('search') }}" onkeypress="if(event.key === 'Enter') { document.getElementById('categoryFilterForm').submit(); return false; }">
              </div>
            </div>

            @if(request()->hasAny(['status', 'created_date', 'search']))
              <div class="col-auto">
                <a href="{{ route('faq-categories.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                  <i data-lucide="rotate-ccw" class="icon-sm me-1"></i> Reset
                </a>
              </div>
            @endif

          </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr class="bg-light">
                <th style="width: 50px;">#</th>
                <th>CATEGORY NAME</th>
                <th>DESCRIPTION</th>
                <th>FAQS COUNT</th>
                <th>CREATED BY</th>
                <th>CREATED AT</th>
                <th>STATUS</th>
                <th class="text-center" style="width: 130px;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $category)
                <tr>
                  <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                  <td>
                    <span class="badge bg-light text-dark border fs-12px fw-medium">
                      <i data-lucide="folder" class="icon-xs me-1 text-primary"></i> {{ $category->name }}
                    </span>
                  </td>
                  <td>
                    <span class="d-inline-block text-truncate text-secondary fs-13px" style="max-width: 250px;" title="{{ $category->description }}">
                      {{ $category->description ?: '-' }}
                    </span>
                  </td>
                  <td>
                    <a href="{{ route('faqs.index', ['category' => $category->name]) }}" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" title="View FAQs in this category">
                      <i data-lucide="help-circle" class="icon-xs me-1"></i> {{ $category->faqs_count ?? 0 }} FAQs
                    </a>
                  </td>
                  <td>
                    <span class="fs-12px text-secondary">{{ $category->creator?->name ?? 'Admin' }}</span>
                  </td>
                  <td>
                    <span class="fs-12px text-secondary">{{ $category->created_at ? $category->created_at->format('d M Y, h:i A') : '-' }}</span>
                  </td>
                  <td>
                    <form action="{{ route('faq-categories.toggle-status', $category->id) }}" method="POST" class="d-inline">
                      @csrf
                      @method('PATCH')
                      <button type="button" class="btn btn-link p-0 text-decoration-none btn-toggle-cat-status" 
                              data-name="{{ $category->name }}" 
                              data-action="{{ $category->status ? 'deactivate' : 'activate' }}"
                              title="Click to toggle status">
                        @if($category->status)
                          <span class="badge bg-success text-white">Active</span>
                        @else
                          <span class="badge bg-secondary text-white">Inactive</span>
                        @endif
                      </button>
                    </form>
                  </td>
                  <td class="text-center">
                    @php
                      $isUsed = ($category->faqs_count ?? 0) > 0;
                    @endphp
                    <div class="d-inline-flex gap-1">
                      <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}" title="Edit Category">
                        <i data-lucide="edit-2" class="icon-xs"></i>
                      </button>
                      @if($isUsed)
                        <button type="button" class="btn btn-xs btn-outline-secondary opacity-50" data-bs-toggle="tooltip" title="Cannot delete: used in {{ $category->faqs_count }} FAQ(s)">
                          <i data-lucide="trash-2" class="icon-xs"></i>
                        </button>
                      @else
                        <button type="button" class="btn btn-xs btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal{{ $category->id }}" title="Delete Category">
                          <i data-lucide="trash-2" class="icon-xs"></i>
                        </button>
                      @endif
                    </div>

                    <!-- Edit Category Modal -->
                    <div class="modal fade text-start" id="editCategoryModal{{ $category->id }}" tabindex="-1" aria-labelledby="editCategoryModalLabel{{ $category->id }}" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="editCategoryModalLabel{{ $category->id }}">Edit FAQ Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <form action="{{ route('faq-categories.update', $category->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                              <div class="mb-3">
                                <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required maxlength="100">
                              </div>
                              <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Category description...">{{ old('description', $category->description) }}</textarea>
                              </div>
                              <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="catStatus{{ $category->id }}" {{ $category->status ? 'checked' : '' }}>
                                <label class="form-check-label" for="catStatus{{ $category->id }}">Active Status</label>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                    @if(!$isUsed)
                    <!-- Delete Category Modal -->
                    <div class="modal fade text-start" id="deleteCategoryModal{{ $category->id }}" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h6 class="modal-title">Delete Category</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <form action="{{ route('faq-categories.destroy', $category->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="modal-body">
                              <p class="fs-13px mb-0">Are you sure you want to delete <strong>{{ $category->name }}</strong>?</p>
                            </div>
                            <div class="modal-footer py-2">
                              <button type="button" class="btn btn-xs btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    @endif

                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-5 text-secondary">
                    <div class="py-3">
                      <i data-lucide="folder-x" class="icon-lg opacity-50 mb-2"></i>
                      <p class="fs-14px mb-1">No FAQ categories found</p>
                      <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                        <i data-lucide="plus-circle" class="icon-sm me-1"></i> Add Category
                      </button>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Pagination --}}
        @if($categories->hasPages())
          <div class="d-flex justify-content-end mt-4">
            {{ $categories->links() }}
          </div>
        @endif

      </div>
    </div>
  </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createCategoryModalLabel">Create New FAQ Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('faq-categories.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Technical Support, Billing, Products" required maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Category purpose or notes..."></textarea>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="catStatusNew" checked>
            <label class="form-check-label" for="catStatusNew">Active Status</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Category</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script src="{{ asset('build/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Confirmation for Category status toggle
  document.querySelectorAll('.btn-toggle-cat-status').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const form = this.closest('form');
      const catName = this.getAttribute('data-name') || 'this category';
      const action = this.getAttribute('data-action') || 'toggle';
      const isDeactivating = (action === 'deactivate');

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Update Category Status?',
          html: `Are you sure you want to <strong>${action}</strong> category <strong>"${catName}"</strong>?`,
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
        if (confirm(`Are you sure you want to ${action} category "${catName}"?`)) {
          form.submit();
        }
      }
    });
  });
});
</script>
@endpush
