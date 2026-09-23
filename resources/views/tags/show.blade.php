@extends('layout.master')

@section('title', 'Tag Details')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('tags.index') }}">Tags</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $tag->id }}</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12">
    <!-- SECTION 1: TAG INFORMATION -->
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title text-uppercase mb-4 text-muted">SECTION 1: TAG INFORMATION</h6>
        
        <div class="row mb-3">
          <div class="col-sm-3 text-muted">Tag ID</div>
          <div class="col-sm-3 fw-bold">{{ $tag->tag_id }}</div>
          <div class="col-sm-3 text-muted">Tag Name</div>
          <div class="col-sm-3 fw-bold">{{ $tag->tag_name }}</div>
        </div>
        
        <div class="row mb-3">
          <div class="col-sm-3 text-muted">Description</div>
          <div class="col-sm-9 fw-bold">{{ $tag->description ?? '-' }}</div>
        </div>
        
        <div class="row mb-3 align-items-center">
          <div class="col-sm-3 text-muted">Status</div>
          <div class="col-sm-3">
            @if($tag->status)
              <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><span class="bg-success rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Active</span>
            @else
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><span class="bg-danger rounded-circle d-inline-block me-1" style="width:6px;height:6px;"></span> Inactive</span>
            @endif
          </div>
          <div class="col-sm-3 text-muted">Created By</div>
          <div class="col-sm-3">
            @if($tag->creator)
              <div class="d-flex align-items-center fw-bold">
                <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($tag->creator->name) . '&background=random&rounded=true') }}" alt="avatar" class="wd-20 ht-20 rounded-circle me-2">
                {{ $tag->creator->name }}
              </div>
            @else
              <span class="fw-bold">-</span>
            @endif
          </div>
        </div>
        
        <div class="row mb-4">
          <div class="col-sm-3 text-muted">Created At</div>
          <div class="col-sm-3 fw-bold">{{ $tag->created_at->format('d M Y, h:i A') }}</div>
          <div class="col-sm-3 text-muted">Updated At</div>
          <div class="col-sm-3 fw-bold">{{ $tag->updated_at->format('d M Y, h:i A') }}</div>
        </div>
        
        <hr>
        
        @if(auth()->id() === 1)
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-outline-primary btn-sm px-4" data-bs-toggle="modal" data-bs-target="#editTagModal">
            <i data-lucide="edit" class="icon-sm me-1"></i> Edit Tag
          </button>
          
          <form action="{{ route('tags.destroy', $tag->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tag?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm px-4">
              <i data-lucide="trash-2" class="icon-sm me-1"></i> Delete
            </button>
          </form>
        </div>
        @endif
        
      </div>
    </div>

    <!-- SECTION 2: TAGGED CONTACTS -->
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-uppercase mb-1 text-muted">SECTION 2: TAGGED CONTACTS</h6>
        <p class="text-muted mb-4 small">Show all contacts to whom this tag has been assigned.</p>
        
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th class="pt-0">CONTACT ID</th>
                <th class="pt-0">CUSTOMER NAME</th>
                <th class="pt-0">WHATSAPP PROFILE NAME</th>
                <th class="pt-0">MOBILE NUMBER</th>
                <th class="pt-0">EMAIL</th>
                <th class="pt-0">OTHER TAGS</th>
                <th class="pt-0">LAST CONVERSATION</th>
                <th class="pt-0">CONTACT CREATED AT</th>
                <th class="pt-0 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($contacts as $contact)
                <tr>
                  <td>#CUS-{{ $contact->id }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($contact->custom_name ?? $contact->whatsapp_profile_name ?? 'User') . '&background=random&rounded=true') }}" alt="avatar" class="wd-30 ht-30 rounded-circle me-2">
                      <span>{{ $contact->custom_name ?? '-' }}</span>
                    </div>
                  </td>
                  <td>{{ $contact->whatsapp_profile_name ?? '-' }}</td>
                  <td>{{ $contact->phone_number }}</td>
                  <td>{{ $contact->email ?? '-' }}</td>
                  <td>
                    @php
                       $otherTags = $contact->tags->where('id', '!=', $tag->id);
                    @endphp
                    @if($otherTags->count() > 0)
                      <span class="badge bg-secondary">{{ $otherTags->first()->tag_name }}</span>
                      @if($otherTags->count() > 1)
                        <span class="badge bg-light text-dark border" title="{{ $otherTags->skip(1)->pluck('tag_name')->join(', ') }}">
                          +{{ $otherTags->count() - 1 }} more
                        </span>
                      @endif
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($contact->conversations->first())
                      {{ $contact->conversations->first()->created_at->diffForHumans() }}
                    @else
                      -
                    @endif
                  </td>
                  <td>{{ $contact->created_at->format('M d, Y h:i A') }}</td>
                  <td class="text-center">
                    <a href="{{ route('contacts.show', $contact->id) }}" class="btn btn-sm btn-light btn-icon" title="View Contact">
                      <i data-lucide="eye" class="icon-sm"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-muted py-4">No contacts found with this tag.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        
        <div class="d-flex justify-content-between mt-3 align-items-center">
          <div class="text-muted small">Showing {{ $contacts->firstItem() ?? 0 }} to {{ $contacts->lastItem() ?? 0 }} of {{ $contacts->total() }} contacts</div>
          <div>
            {{ $contacts->links() }}
          </div>
        </div>
        
      </div>
    </div>
  </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTagModalLabel">Edit Tag</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
      </div>
      <form action="{{ route('tags.update', $tag->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <p class="text-muted mb-3 text-sm">Update the details for this tag.</p>
          
          <div class="mb-3">
            <label for="tag_name" class="form-label">Tag Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('tag_name') is-invalid @enderror" id="tag_name" name="tag_name" value="{{ old('tag_name', $tag->tag_name) }}" required>
            @error('tag_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $tag->description) }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          
          <div class="d-flex justify-content-between align-items-center mb-1 mt-4">
            <div>
              <h6 class="mb-1">Active Status</h6>
              <p class="text-muted small mb-0">Active tags can be assigned to contacts across campaigns.</p>
            </div>
            <div class="form-check form-switch">
              <input type="checkbox" class="form-check-input" id="status" name="status" value="1" {{ $tag->status ? 'checked' : '' }}>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Tag</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/jquery/jquery.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    @if($errors->any())
      $('#editTagModal').modal('show');
    @endif
  });
</script>
@endpush
