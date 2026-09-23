@extends('layout.master')

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
                <th class="pt-0">NAME</th>
                <th class="pt-0">EMAIL</th>
                <th class="pt-0">CONTACT NUMBER</th>
                <th class="pt-0">STATUS</th>
                <th class="pt-0 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($companies as $company)
                <tr>
                  <td>{{ $company->name }}</td>
                  <td>{{ $company->contact_email }}</td>
                  <td>{{ $company->contact_number ?? '-' }}</td>
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
                        <button type="submit" class="btn btn-sm btn-light btn-icon" title="Toggle Status">
                            <i data-lucide="{{ $company->is_active ? 'toggle-right' : 'toggle-left' }}" class="icon-sm {{ $company->is_active ? 'text-success' : 'text-danger' }}"></i>
                        </button>
                    </form>
                    <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-light btn-icon" title="Edit">
                      <i data-lucide="edit" class="icon-sm"></i>
                    </a>
                    <form action="{{ route('companies.destroy', $company->id) }}" method="POST" class="d-inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this company? All associated users will also be deleted.');">
                            <i data-lucide="trash" class="icon-sm"></i>
                        </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No companies found.</td>
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
