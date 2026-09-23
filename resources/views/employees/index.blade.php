@extends('layout.master')

@section('title', 'Employees')

@section('content')
@php
  $isAdmin = false;
  if (auth()->check()) {
      if (auth()->id() === 1) {
          $isAdmin = true;
      } else {
          $emp = \App\Models\Employee::where('email', auth()->user()->email)->first();
          if ($emp && $emp->role === 'ADMIN') {
              $isAdmin = true;
          } elseif (!$emp) {
              $isAdmin = true;
          }
      }
  }
@endphp
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item active" aria-current="page">Employees</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title mb-0">Employees</h6>
          <div class="d-flex align-items-center gap-2">
            @if($isAdmin)
            <a href="{{ route('employees.create') }}" class="btn btn-sm btn-primary">
              <i data-lucide="plus-circle" class="icon-sm me-1"></i> Add Employee
            </a>
            @endif
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        
        <form action="{{ route('employees.index') }}" method="GET" id="filterForm">
          <div class="row g-2 mb-4 align-items-center">
            
            <div class="col-md-2">
              <select name="status" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Status</option>
                <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                <option value="INVITED" {{ request('status') == 'INVITED' ? 'selected' : '' }}>Invited</option>
                <option value="BLOCKED" {{ request('status') == 'BLOCKED' ? 'selected' : '' }}>Blocked</option>
              </select>
            </div>
            
            <div class="col-md-3 ms-auto">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent"><i data-lucide="search" class="icon-sm text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search Employee" value="{{ request('search') }}" onkeypress="if(event.key === 'Enter') { document.getElementById('filterForm').submit(); return false; }">
              </div>
            </div>

          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th class="pt-0">CODE</th>
                <th class="pt-0">NAME</th>
                <th class="pt-0">EMAIL</th>
                <th class="pt-0">DEPARTMENT</th>
                <th class="pt-0">ROLE</th>
                <th class="pt-0">STATUS</th>
                @if($isAdmin)
                <th class="pt-0 text-center">ACTION</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse($employees as $employee)
                <tr>
                  <td>{{ $employee->employee_code }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($employee->display_name) . '&background=random&rounded=true') }}" alt="avatar" class="wd-30 ht-30 rounded-circle me-2">
                      <span>{{ $employee->display_name }}<br><small class="text-muted">{{ $employee->designation }}</small></span>
                    </div>
                  </td>
                  <td>{{ $employee->email }}</td>
                  <td>{{ $employee->department ?? '-' }}</td>
                  <td><span class="badge bg-secondary">{{ $employee->role }}</span></td>
                  <td>
                    @if($employee->status == 'ACTIVE')
                      <span class="badge bg-success-subtle text-success border border-success-subtle"><span class="bg-success rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Active</span>
                    @elseif($employee->status == 'INACTIVE')
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><span class="bg-danger rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Inactive</span>
                    @elseif($employee->status == 'INVITED')
                      <span class="badge bg-info-subtle text-info border border-info-subtle"><span class="bg-info rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Invited</span>
                    @else
                      <span class="badge bg-dark-subtle text-dark border border-dark-subtle"><span class="bg-dark rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Blocked</span>
                    @endif
                  </td>
                  @if($isAdmin)
                  <td class="text-center">
                    <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-sm btn-light btn-icon" title="Edit">
                      <i data-lucide="edit" class="icon-sm"></i>
                    </a>
                    <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this employee?');">
                            <i data-lucide="trash" class="icon-sm"></i>
                        </button>
                    </form>
                  </td>
                  @endif
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">No employees found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3 align-items-center">
          <div class="text-muted small">Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} entries</div>
          <div>
            {{ $employees->links() }}
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
