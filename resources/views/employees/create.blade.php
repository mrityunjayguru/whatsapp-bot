@extends('layout.master')

@section('title', 'Add Employee')

@php
  $catalog = $catalog ?? \App\Services\RolePermissionService::getPermissionCatalog();
  $allPermissions = $allPermissions ?? \App\Models\Permission::all()->groupBy('module');
@endphp

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <h6 class="card-title mb-4">Add New Employee</h6>

        <form action="{{ route('employees.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}">
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <div class="row">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Email address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" class="form-control @error('mobile_number') is-invalid @enderror" name="mobile_number" value="{{ old('mobile_number') }}">
                        @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <div class="row">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label" for="department">Department</label>
                        <select class="form-select @error('department') is-invalid @enderror" name="department" id="department">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->name }}" {{ old('department') == $dept->name ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label" for="designation">Designation</label>
                        <select class="form-select @error('designation') is-invalid @enderror" name="designation" id="designation">
                            <option value="">Select Designation</option>
                            @foreach($designations as $desig)
                                <option value="{{ $desig->name }}" data-department-id="{{ $desig->department_id }}" {{ old('designation') == $desig->name ? 'selected' : '' }}>
                                    {{ $desig->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('designation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <div class="row">
                <div class="col-sm-4">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Role <span class="text-danger">*</span></label>
                        </div>
                        <select class="form-select @error('role_id') is-invalid @enderror" name="role_id" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : (old('role') == strtoupper($role->slug) ? 'selected' : '') }}>
                                    {{ $role->name }} {{ $role->is_system ? '(System)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-4">
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                            <option value="INACTIVE" {{ old('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            <!-- <option value="INVITED" {{ old('status') == 'INVITED' ? 'selected' : '' }}>Invited</option>
                            <option value="BLOCKED" {{ old('status') == 'BLOCKED' ? 'selected' : '' }}>Blocked</option> -->
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
                <div class="col-sm-4">
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div><!-- Col -->
            </div><!-- Row -->

            <div class="row">
                <div class="col-6">
                    <div class="card border mb-4" style="background-color: #f8fafc;">
                        <div class="card-body py-3 px-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="me-3">
                                    <div class="d-flex align-items-center mb-1">
                                        <i data-lucide="user-check" class="icon-sm text-primary me-2"></i>
                                        <h6 class="mb-0 fw-bold text-dark">Chat Assignment / Transfer Permission</h6>
                                    </div>
                                    <p class="text-muted small mb-0">Allow this employee to assign or transfer conversations to other active team members.</p>
                                </div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input type="checkbox" class="form-check-input cursor-pointer" id="can_assign_chats" name="can_assign_chats" value="1" {{ old('can_assign_chats') ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 pt-2">
                <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary px-3">Cancel</a>
            </div>
        </form>

      </div>
    </div>
  </div>
</div>

@endsection
