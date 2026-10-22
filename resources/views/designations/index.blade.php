@extends('layout.master')

@section('title', 'Designations')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('designations.index') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
  </ol>
  <div class="d-flex gap-2">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDesignationModal">
      <i data-lucide="plus-circle" class="icon-sm me-1"></i> Add Designation
    </button>
  </div>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Company Designations</h6>
            <p class="text-secondary fs-13px mb-0">Manage job titles and designations for your company employees and chat agents.</p>
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

        <!-- Filter & Search Form -->
        <form method="GET" action="{{ route('designations.index') }}" class="row g-2 mb-4 align-items-center">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text"><i data-lucide="search" class="icon-sm"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Search designations..." value="{{ request('search') }}">
            </div>
          </div>
          <div class="col-md-3">
            <select name="department_id" class="form-select" onchange="this.form.submit()">
              <option value="">All Departments</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                  {{ $dept->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
              <option value="">All Statuses</option>
              <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request()->hasAny(['search', 'department_id', 'status']))
              <a href="{{ route('designations.index') }}" class="btn btn-outline-light">Reset</a>
            @endif
          </div>
        </form>

        <!-- Designations Table -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th>Designation Title</th>
                <th>Department</th>
                <th>Description</th>
                <th style="width: 140px;" class="text-center">Assigned Staff</th>
                <th style="width: 120px;" class="text-center">Status</th>
                <th style="width: 160px;" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($designations as $index => $designation)
                <tr>
                  <td>{{ $designations->firstItem() + $index }}</td>
                  <td>
                    <span class="fw-bold text-dark">{{ $designation->name }}</span>
                  </td>
                  <td>
                    @if($designation->department)
                      <span class="badge bg-light text-dark border">{{ $designation->department->name }}</span>
                    @else
                      <span class="text-muted small">General</span>
                    @endif
                  </td>
                  <td>
                    <span class="text-secondary small">{{ $designation->description ? Str::limit($designation->description, 60) : '-' }}</span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-bold">
                      <i data-lucide="users" class="icon-xs me-1"></i> {{ $designation->employees_count ?? 0 }}
                    </span>
                  </td>
                  <td class="text-center">
                    <form action="{{ route('designations.toggle-status', $designation->id) }}" method="POST" class="d-inline">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="badge rounded-pill border-0 {{ $designation->status ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} px-3 py-1 cursor-pointer" style="cursor: pointer;" title="Click to toggle status">
                        {{ $designation->status ? 'Active' : 'Inactive' }}
                      </button>
                    </form>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <button type="button" class="btn btn-xs btn-outline-primary btn-edit-desig"
                              data-id="{{ $designation->id }}"
                              data-name="{{ $designation->name }}"
                              data-department-id="{{ $designation->department_id }}"
                              data-description="{{ $designation->description }}"
                              data-status="{{ $designation->status ? '1' : '0' }}"
                              data-employees-count="{{ $designation->employees_count ?? 0 }}"
                              data-bs-toggle="modal" data-bs-target="#editDesignationModal"
                              title="Edit Designation">
                        <i data-lucide="edit-2" class="icon-xs"></i> Edit
                      </button>

                      <form action="{{ route('designations.destroy', $designation->id) }}" method="POST" class="d-inline delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-xs btn-outline-danger btn-delete-desig"
                                data-name="{{ $designation->name }}"
                                data-count="{{ $designation->employees_count ?? 0 }}"
                                title="Delete Designation">
                          <i data-lucide="trash-2" class="icon-xs"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i data-lucide="briefcase" class="icon-lg text-muted mb-2" style="width: 42px; height: 42px;"></i>
                    <p class="mb-1 fw-semibold">No designations found.</p>
                    <small>Click "Add Designation" above to create your company's roles and job titles.</small>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($designations->hasPages())
          <div class="mt-4 d-flex justify-content-end">
            {{ $designations->links() }}
          </div>
        @endif

      </div>
    </div>
  </div>
</div>

<!-- Modal: Create Designation -->
<div class="modal fade" id="createDesignationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('designations.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i data-lucide="plus-circle" class="icon-sm text-primary me-1"></i> Add Designation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-bold">Department</label>
            <select name="department_id" class="form-select">
              @foreach($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Designation Title <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Senior Support Agent, Sales Executive" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Brief summary of duties or scope..."></textarea>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="status" id="createDesigStatusSwitch" value="1" checked>
            <label class="form-check-label" for="createDesigStatusSwitch">Active</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-bold">Save Designation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Designation -->
<div class="modal fade" id="editDesignationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editDesignationForm" action="" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i data-lucide="edit-2" class="icon-sm text-primary me-1"></i> Edit Designation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
           <div class="mb-3">
            <label class="form-label fw-bold">Department</label>
            <select name="department_id" id="editDesigDept" class="form-select">
              @foreach($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Designation Title <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editDesigName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" id="editDesigDescription" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="status" id="editDesigStatus" value="1">
            <label class="form-check-label" for="editDesigStatus">Active</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-bold">Update Designation</button>
        </div>
      </form>
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
    // Populate Edit Designation Modal
    const editButtons = document.querySelectorAll('.btn-edit-desig');
    const editForm = document.getElementById('editDesignationForm');
    const editName = document.getElementById('editDesigName');
    const editDept = document.getElementById('editDesigDept');
    const editDesc = document.getElementById('editDesigDescription');
    const editStatus = document.getElementById('editDesigStatus');

    editButtons.forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const name = this.getAttribute('data-name');
        const deptId = this.getAttribute('data-department-id');
        const desc = this.getAttribute('data-description');
        const status = this.getAttribute('data-status') === '1';

        editForm.action = "{{ url('designations') }}/" + id;
        editName.value = name || '';
        editDept.value = deptId || '';
        editDesc.value = desc || '';
        editStatus.checked = status;
      });
    });

    // Confirmation for Delete Designation
    const deleteButtons = document.querySelectorAll('.btn-delete-desig');
    deleteButtons.forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const desigName = this.getAttribute('data-name');
        const assignedCount = parseInt(this.getAttribute('data-count') || '0', 10);

        if (assignedCount > 0) {
          Swal.fire({
            title: 'Cannot Delete Designation',
            text: `Designation "${desigName}" is assigned to ${assignedCount} employee(s). Please reassign them before deleting.`,
            icon: 'warning',
            confirmButtonClass: 'btn btn-primary'
          });
          return;
        }

        Swal.fire({
          title: 'Delete Designation?',
          text: `Are you sure you want to delete "${desigName}"? This action cannot be undone.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });
  });
</script>
@endpush
