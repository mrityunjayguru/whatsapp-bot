@extends('layout.master')

@section('title', 'Lead Statuses')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Lead Statuses</h4>
  </div>
  <div>
    @if(auth()->user()->hasPermission('lead-status.create'))
    <a href="{{ route('lead-status.create') }}" class="btn btn-primary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="plus"></i>
      Add Lead Status
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
                <th>Status</th>
                <th>Created By</th>
                @if(auth()->user()->hasPermission('lead-status.edit') || auth()->user()->hasPermission('lead-status.delete'))
                <th>Actions</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse($leadStatuses as $leadStatus)
              <tr>
                <td>{{ $loop->iteration + ($leadStatuses->currentPage() - 1) * $leadStatuses->perPage() }}</td>
                <td>{{ $leadStatus->name }}</td>
                <td>
                  @if($leadStatus->status)
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-danger">Inactive</span>
                  @endif
                </td>
                <td>{{ $leadStatus->creator->name ?? '—' }}</td>
                @if(auth()->user()->hasPermission('lead-status.edit') || auth()->user()->hasPermission('lead-status.delete'))
                <td>
                  @if(auth()->user()->hasPermission('lead-status.edit'))
                  <a href="{{ route('lead-status.edit', $leadStatus) }}" class="btn btn-sm btn-outline-primary me-1">
                    <i data-lucide="edit-2" class="icon-sm"></i>
                  </a>
                  @endif
                  @if(auth()->user()->hasPermission('lead-status.delete'))
                  <form action="{{ route('lead-status.destroy', $leadStatus) }}" method="POST" class="d-inline delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                      class="btn btn-sm btn-outline-danger btn-delete"
                      data-name="{{ $leadStatus->name }}">
                      <i data-lucide="trash-2" class="icon-sm"></i>
                    </button>
                  </form>
                  @endif
                </td>
                @endif
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">No lead statuses found.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($leadStatuses->hasPages())
          <div class="d-flex justify-content-end mt-3">
            {{ $leadStatuses->links() }}
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
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
