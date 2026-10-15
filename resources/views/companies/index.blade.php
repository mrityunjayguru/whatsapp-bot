@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('title', 'Companies')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item active" aria-current="page">Companies</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title mb-0">Companies</h6>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('companies.create') }}" class="btn btn-sm btn-primary">
              <i data-lucide="plus-circle" class="icon-sm me-1"></i> Add Company
            </a>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        
        <form action="{{ route('companies.index') }}" method="GET" id="filterForm">
          <div class="row g-2 mb-4 align-items-center">
            
            <div class="col-md-2">
              <select name="status" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Status</option>
                <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>
            
            <div class="col-md-3 ms-auto">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent"><i data-lucide="search" class="icon-sm text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search Company" value="{{ request('search') }}" onkeypress="if(event.key === 'Enter') { document.getElementById('filterForm').submit(); return false; }">
              </div>
            </div>

          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th class="pt-0">COMPANY ID</th>
                <th class="pt-0">NAME</th>
                <th class="pt-0">EMAIL</th>
                <th class="pt-0">CONTACT NUMBER</th>
                <th class="pt-0">BOT TYPE</th>
                <th class="pt-0">STATUS</th>
                <th class="pt-0 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($companies as $company)
                <tr>
                  <td>
                    <span class="badge bg-light text-dark border fw-bold font-monospace">{{ $company->company_code ?? '-' }}</span>
                  </td>
                  <td>{{ $company->name }}</td>
                  <td>{{ $company->contact_email }}</td>
                  <td>{{ $company->contact_number ?? '-' }}</td>
                  <td>
                    @if($company->bot_usage_type === 'widget')
                      <span class="badge bg-info-subtle text-info border border-info-subtle"><i data-lucide="layout-grid" class="icon-xs me-1"></i> Widget Bot</span>
                    @else
                      <span class="badge bg-success-subtle text-success border border-success-subtle"><i data-lucide="message-circle" class="icon-xs me-1"></i> WhatsApp Bot</span>
                    @endif
                  </td>
                  <td>
                    @if($company->is_active)
                      <span class="badge bg-success-subtle text-success border border-success-subtle"><span class="bg-success rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Active</span>
                    @else
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><span class="bg-danger rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Inactive</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <form action="{{ route('companies.toggle-status', $company->id) }}" method="POST" class="d-inline-block">
                        @csrf
                        @method('PATCH')
                        <button type="button" 
                                class="btn btn-sm btn-light btn-icon btn-toggle-status" 
                                title="Toggle Status"
                                data-name="{{ $company->name }}"
                                data-action="{{ $company->is_active ? 'deactivate' : 'activate' }}">
                            <i data-lucide="{{ $company->is_active ? 'toggle-right' : 'toggle-left' }}" class="icon-sm {{ $company->is_active ? 'text-success' : 'text-danger' }}"></i>
                        </button>
                    </form>
                    <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-light btn-icon" title="Edit">
                      <i data-lucide="edit" class="icon-sm"></i>
                    </a>
                    <form action="{{ route('companies.destroy', $company->id) }}" method="POST" class="d-inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="button" 
                                class="btn btn-sm btn-light btn-icon text-danger btn-delete-company" 
                                title="Delete"
                                data-name="{{ $company->name }}">
                            <i data-lucide="trash" class="icon-sm"></i>
                        </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">No companies found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3 align-items-center">
          <div class="text-muted small">Showing {{ $companies->firstItem() ?? 0 }} to {{ $companies->lastItem() ?? 0 }} of {{ $companies->total() }} entries</div>
          <div>
            {{ $companies->links() }}
          </div>
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
  // SweetAlert for Company Toggle Status
  document.querySelectorAll('.btn-toggle-status').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const form = this.closest('form');
      const companyName = this.getAttribute('data-name') || 'this company';
      const action = this.getAttribute('data-action') || 'toggle';
      const isDeactivating = (action === 'deactivate');

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Update Status?',
          html: `Are you sure you want to <strong>${action}</strong> <strong>${companyName}</strong>?`,
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
        if (confirm(`Are you sure you want to ${action} ${companyName}?`)) {
          form.submit();
        }
      }
    });
  });

  // SweetAlert for Company Delete
  document.querySelectorAll('.btn-delete-company').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const form = this.closest('form');
      const companyName = this.getAttribute('data-name') || 'this company';

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Delete Company?',
          html: `Are you sure you want to delete <strong>${companyName}</strong>?<br><small class="text-danger">All associated users will also be deleted.</small>`,
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
        if (confirm(`Are you sure you want to delete ${companyName}? All associated users will also be deleted.`)) {
          form.submit();
        }
      }
    });
  });
});
</script>
@endpush
