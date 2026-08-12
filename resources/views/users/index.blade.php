@extends('layout.master')

@section('title', 'Users')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Users</h4>
  </div>
  <div>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="user-plus"></i>
      Add User
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
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $user)
              <tr>
                <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                <td class="fw-semibold">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                  <span class="badge bg-info text-dark">
                    {{ $user->role->display_name ?? '—' }}
                  </span>
                </td>
                <td>
                  @if($user->status)
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-danger">Inactive</span>
                  @endif
                </td>
                <td>
                  <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary me-1">
                    <i data-lucide="edit-2" class="icon-sm"></i>
                  </a>
                  <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                      data-name="{{ $user->name }}">
                      <i data-lucide="trash-2" class="icon-sm"></i>
                    </button>
                  </form>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  No users found. <a href="{{ route('users.create') }}">Add your first user</a>.
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($users->hasPages())
          <div class="d-flex justify-content-end mt-3">
            {{ $users->links() }}
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
        text: 'This action cannot be undone.',
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
