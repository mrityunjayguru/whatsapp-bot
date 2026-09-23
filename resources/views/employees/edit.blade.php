@extends('layout.master')

@section('title', 'Edit Employee')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <h6 class="card-title mb-4">Edit Employee: {{ $employee->display_name }} ({{ $employee->employee_code }})</h6>

        <form action="{{ route('employees.update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $employee->last_name) }}">
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <div class="row">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Email address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $employee->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" class="form-control @error('mobile_number') is-invalid @enderror" name="mobile_number" value="{{ old('mobile_number', $employee->mobile_number) }}">
                        @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <div class="row">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control @error('department') is-invalid @enderror" name="department" value="{{ old('department', $employee->department) }}">
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Designation</label>
                        <input type="text" class="form-control @error('designation') is-invalid @enderror" name="designation" value="{{ old('designation', $employee->designation) }}">
                        @error('designation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <div class="row">
                <div class="col-sm-4">
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" name="role" required>
                            <option value="EMPLOYEE" {{ old('role', $employee->role) == 'EMPLOYEE' ? 'selected' : '' }}>Employee</option>
                            <option value="MANAGER" {{ old('role', $employee->role) == 'MANAGER' ? 'selected' : '' }}>Manager</option>
                            <option value="ADMIN" {{ old('role', $employee->role) == 'ADMIN' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-4">
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="ACTIVE" {{ old('status', $employee->status) == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                            <option value="INACTIVE" {{ old('status', $employee->status) == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            <option value="INVITED" {{ old('status', $employee->status) == 'INVITED' ? 'selected' : '' }}>Invited</option>
                            <option value="BLOCKED" {{ old('status', $employee->status) == 'BLOCKED' ? 'selected' : '' }}>Blocked</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-4">
                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <button type="submit" class="btn btn-primary submit">Update Employee</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
        </form>

      </div>
    </div>
  </div>
</div>

@endsection
