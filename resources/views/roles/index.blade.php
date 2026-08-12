@extends('layout.master')

@section('title', 'Roles & Permissions')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Roles & Permissions</h4>
  </div>
  <div>
    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="plus"></i>
      Add Role
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Role Name</th>
                <th>Permissions</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roles as $role)
              <tr>
                <td>{{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}</td>
                <td>
                  <span class="fw-semibold">{{ $role->display_name }}</span>
                  <br><small class="text-muted">{{ $role->name }}</small>
                </td>
                <td>
                  @if($role->permissions_count > 0)
                    <span class="badge bg-primary">{{ $role->permissions_count }} permissions</span>
                  @else
                    <span class="badge bg-secondary">No permissions</span>
                  @endif
                </td>
                <td>
                  <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit & Assign Permissions">
                    <i data-lucide="shield" class="icon-sm"></i>
                  </a>
                  <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-name="{{ $role->display_name }}">
                      <i data-lucide="trash-2" class="icon-sm"></i>
                    </button>
                  </form>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">No roles found. <a href="{{ route('roles.create') }}">Create your first role</a>.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($roles->hasPages())
          <div class="d-flex justify-content-end mt-3">
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
  lucide.createIcons();

  document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const name = this.dataset.name;
      const form = this.closest('form');
      Swal.fire({
        title: 'Delete "' + name + '"?',
        text: 'All permissions assigned to this role will also be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
      }).then(function (result) {
        if (result.isConfirmed) form.submit();
      });
    });
  });
</script>
@endpush
