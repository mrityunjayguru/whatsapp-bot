@extends('layout.master')

@section('title', 'Tags')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item active" aria-current="page">Tags</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title mb-0">Tags</h6>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createTagModal">
              <i data-lucide="plus-circle" class="icon-sm me-1"></i> Create Tag
            </button>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        
        <form action="{{ route('tags.index') }}" method="GET" id="filterForm">
          <div class="row g-2 mb-4 align-items-center">
            
            <div class="col-md-2">
              <select name="status" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>
            
            <div class="col-md-3">
              <div class="input-group input-group-sm flatpickr" id="createdDateFlatpickr">
                <span class="input-group-text bg-transparent"><i data-lucide="calendar" class="icon-sm text-muted"></i></span>
                <input type="text" name="created_date" class="form-control" placeholder="Created Date" value="{{ request('created_date') }}" data-input>
              </div>
            </div>
            
            <div class="col-md-3 ms-auto">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent"><i data-lucide="search" class="icon-sm text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search Tag" value="{{ request('search') }}">
              </div>
            </div>

          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th class="pt-0">
                  <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" id="checkAll">
                  </div>
                </th>
                <th class="pt-0">TAG ID</th>
                <th class="pt-0">TAG NAME</th>
                <th class="pt-0">DESCRIPTION</th>
                <th class="pt-0">NUMBER OF CONTACTS</th>
                <th class="pt-0">CREATED BY</th>
                <th class="pt-0">CREATED AT</th>
                <th class="pt-0">UPDATED AT</th>
                <th class="pt-0">STATUS</th>
                <th class="pt-0 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tags as $tag)
                <tr>
                  <td>
                    <div class="form-check form-check-inline">
                      <input type="checkbox" class="form-check-input row-checkbox" value="{{ $tag->id }}">
                    </div>
                  </td>
                  <td>{{ $tag->tag_id }}</td>
                  <td>
                    <span class="badge bg-light text-dark border"><i data-lucide="tag" class="icon-sm me-1 text-primary"></i> {{ $tag->tag_name }}</span>
                  </td>
                  <td>
                    <span class="d-inline-block text-truncate" style="max-width: 200px;" title="{{ $tag->description }}">
                      {{ $tag->description ?? '-' }}
                    </span>
                  </td>
                  <td>
                    <div class="d-flex align-items-center text-muted">
                      <i data-lucide="users" class="icon-sm me-1"></i> {{ $tag->contacts_count }} Contacts
                    </div>
                  </td>
                  <td>
                    @if($tag->creator)
                      <div class="d-flex align-items-center">
                        <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($tag->creator->name) . '&background=random&rounded=true') }}" alt="avatar" class="wd-30 ht-30 rounded-circle me-2">
                        <span>{{ $tag->creator->name }}</span>
                      </div>
                    @else
                      -
                    @endif
                  </td>
                  <td>{{ $tag->created_at->format('d M Y, h:i A') }}</td>
                  <td>{{ $tag->updated_at->format('d M Y, h:i A') }}</td>
                  <td>
                    @if($tag->status)
                      <span class="badge bg-success-subtle text-success border border-success-subtle"><span class="bg-success rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Active</span>
                    @else
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><span class="bg-danger rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Inactive</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-light btn-icon" title="View">
                      <i data-lucide="eye" class="icon-sm"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="text-center text-muted py-4">No tags found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3 align-items-center">
          <div class="text-muted small" id="selectedCountText">0 of {{ $tags->total() }} row(s) selected.</div>
          <div>
            {{ $tags->links() }}
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Create Tag Modal -->
<div class="modal fade" id="createTagModal" tabindex="-1" aria-labelledby="createTagModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createTagModalLabel">Create New Tag</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
      </div>
      <form action="{{ route('tags.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <p class="text-muted mb-3 text-sm">Add a new tag to organize contacts and segment leads efficiently.</p>
          
          <div class="mb-3">
            <label for="tag_name" class="form-label">Tag Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="tag_name" name="tag_name" placeholder="e.g. Enterprise Client" required>
          </div>
          
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of the tag and its intended use..."></textarea>
          </div>
          
          <div class="d-flex justify-content-between align-items-center mb-1 mt-4">
            <div>
              <h6 class="mb-1">Active Status</h6>
              <p class="text-muted small mb-0">Active tags can be assigned to contacts across campaigns.</p>
            </div>
            <div class="form-check form-switch">
              <input type="checkbox" class="form-check-input" id="status" name="status" value="1" checked>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Tag</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/flatpickr/flatpickr.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    'use strict';

    if($('#createdDateFlatpickr').length) {
      flatpickr("#createdDateFlatpickr", {
        wrap: true,
        dateFormat: "Y-m-d",
        onChange: function() {
           $('#filterForm').submit();
        }
      });
    }

    // Handle checkboxes
    $('#checkAll').on('change', function() {
      $('.row-checkbox').prop('checked', $(this).prop('checked'));
      updateSelectedCount();
    });

    $('.row-checkbox').on('change', function() {
      if (!$(this).prop('checked')) {
        $('#checkAll').prop('checked', false);
      }
      updateSelectedCount();
    });

    function updateSelectedCount() {
      let count = $('.row-checkbox:checked').length;
      $('#selectedCountText').text(count + ' of {{ $tags->total() }} row(s) selected.');
    }
  });
</script>
@endpush
