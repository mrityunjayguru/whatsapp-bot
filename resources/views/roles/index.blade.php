@extends('layout.master')

@section('title', 'Roles & Permissions')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">Roles & Permissions</li>
  </ol>
  <div class="d-flex gap-2">
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary"><i data-lucide="users" class="icon-sm me-1"></i> Employees</a>
    <a href="{{ route('roles.create') }}" class="btn btn-primary">
      <i data-lucide="plus-circle" class="icon-sm me-1"></i> Add Custom Role
    </a>
  </div>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <div>
            <h6 class="card-title mb-1">Roles & Permissions</h6>
            <p class="text-secondary fs-13px mb-0">Configure custom roles and define granular permission matrices for your team members and chat agents.</p>
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

        <!-- Filter & Search Form -->
        <form method="GET" action="{{ route('roles.index') }}" class="row g-2 mb-4 align-items-center">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text"><i data-lucide="search" class="icon-sm"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Search roles by title or description..." value="{{ request('search') }}">
            </div>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-secondary">Search</button>
            @if(request()->has('search'))
              <a href="{{ route('roles.index') }}" class="btn btn-outline-light">Reset</a>
            @endif
          </div>
        </form>

        <!-- Roles Table -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th style="min-width: 180px;">Role Name</th>
                <th>Description</th>
                <th style="width: 140px;">Permissions</th>
                {{-- <th style="width: 130px;" class="text-center">Assigned Users</th> --}}
                <th style="width: 100px;" class="text-center">Type</th>
                <th style="width: 160px;" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roles as $index => $role)
                @php
                  $permsCount = strtoupper($role->slug) === 'ADMIN' ? $totalPermissionsCount : $role->permissions->count();
                @endphp
                <tr>
                  <td>{{ $roles->firstItem() + $index }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar-xs me-2">
                        <span class="avatar-title rounded-circle {{ $role->is_system ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }} fw-bold p-2">
                          <i data-lucide="shield" class="icon-xs"></i>
                        </span>
                      </div>
                      <div>
                        <span class="fw-bold text-dark d-block">{{ $role->name }}</span>
                        <code class="text-muted fs-11px">{{ $role->slug }}</code>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="text-secondary small">{{ $role->description ? Str::limit($role->description, 80) : '-' }}</span>
                  </td>
                  <td>
                    <span class="fw-semibold text-dark fs-13px">
                      {{ $permsCount }} / {{ $totalPermissionsCount }}
                    </span>
                  </td>
                  {{-- <td class="text-center">
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-bold">
                      <i data-lucide="users" class="icon-xs me-1"></i> {{ $role->employees->count() }}
                    </span>
                  </td> --}}
                  <td class="text-center">
                    @if($role->is_system)
                      <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">System</span>
                    @else
                      <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1">Custom</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-xs btn-outline-primary" title="Configure Permissions">
                        <i data-lucide="sliders" class="icon-xs me-1"></i> Permissions
                      </a>

                      @if(!$role->is_system)
                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline delete-form">
                          @csrf
                          @method('DELETE')
                          <button type="button" class="btn btn-xs btn-outline-danger btn-delete-role"
                                  data-name="{{ $role->name }}"
                                  data-count="{{ $role->employees->count() }}"
                                  title="Delete Role">
                            <i data-lucide="trash-2" class="icon-xs"></i>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i data-lucide="shield-off" class="icon-lg text-muted mb-2" style="width: 42px; height: 42px;"></i>
                    <p class="mb-1 fw-semibold">No roles found.</p>
                    <small>Click "Add Custom Role" to define your own tailored employee permissions.</small>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($roles->hasPages())
          <div class="mt-4 d-flex justify-content-end">
            {{ $roles->links() }}
          </div>
        @endif

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
    const deleteButtons = document.querySelectorAll('.btn-delete-role');
    deleteButtons.forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const roleName = this.getAttribute('data-name');
        const assignedCount = parseInt(this.getAttribute('data-count') || '0', 10);

        if (assignedCount > 0) {
          Swal.fire({
            title: 'Cannot Delete Role',
            text: `Role "${roleName}" is currently assigned to ${assignedCount} employee(s). Please reassign them before deleting.`,
            icon: 'warning',
            confirmButtonClass: 'btn btn-primary'
          });
          return;
        }

        Swal.fire({
          title: 'Delete Role?',
          text: `Are you sure you want to delete role "${roleName}"? This action cannot be undone.`,
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
