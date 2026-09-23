@extends('layout.master')

@section('title', 'Contact Details')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
  <link href="{{ asset('build/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i data-lucide="arrow-left" class="btn-icon-prepend"></i> Back
    </a>
    <h4 class="mb-0 fw-normal text-muted">Contact ID # <span class="text-dark fw-bold">{{ $contact->id }}</span></h4>
  </div>
  <div class="d-flex align-items-center flex-wrap text-nowrap gap-2 mt-3 mt-md-0">
    <button type="button" class="btn btn-outline-secondary btn-icon-text">
      <i data-lucide="mail" class="btn-icon-prepend"></i> Email Contact
    </button>
    <button type="button" class="btn btn-outline-secondary btn-icon-text">
      <i data-lucide="phone" class="btn-icon-prepend"></i> Call Contact
    </button>
    @if(auth()->id() === 1)
    <button type="button" class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#editContactModal">
      <i data-lucide="edit" class="btn-icon-prepend"></i> Edit Contact
    </button>
    @endif
  </div>
</div>

<div class="row">
  <!-- SECTION 1: CONTACT INFORMATION -->
  <div class="col-md-6 col-lg-7 d-flex flex-column">
    <div class="card w-100 grid-margin">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4">SECTION 1: CONTACT INFORMATION</h6>
        
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
          @if($contact->custom_name || $contact->whatsapp_profile_name)
            <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($contact->custom_name ?? $contact->whatsapp_profile_name) . '&background=random&rounded=true') }}" alt="profile" class="wd-50 ht-50 rounded-circle me-3">
          @else
            <img src="{{ url('https://ui-avatars.com/api/?name=User&background=random&rounded=true') }}" alt="profile" class="wd-50 ht-50 rounded-circle me-3">
          @endif
          <div>
            <h5 class="mb-1">{{ $contact->custom_name ?? $contact->whatsapp_profile_name ?? 'Unknown Customer' }}</h5>
            <p class="text-muted tx-13 mb-0">Since {{ $contact->created_at->format('d M Y, h:i A') }}</p>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted">Custom Name</div>
          <div class="col-sm-8">{{ $contact->custom_name ?? $contact->whatsapp_profile_name ?? 'Unknown Customer' }}</div>
        </div>
        
        <div class="row mb-3">
          <div class="col-sm-4 text-muted">WhatsApp Profile Name</div>
          <div class="col-sm-8 text-primary">{{ $contact->whatsapp_profile_name ?? '-' }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted">Phone Number</div>
          <div class="col-sm-8 fw-bold">{{ $contact->phone_number }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted">Email</div>
          <div class="col-sm-8">{{ $contact->email ?? '-' }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted">Pincode</div>
          <div class="col-sm-8 fw-bold">{{ $contact->pincode ?? '-' }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted">Tags</div>
          <div class="col-sm-8">
            @if($contact->tags->count() > 0)
              @foreach($contact->tags as $tag)
                <span class="badge border text-dark bg-light">{{ $tag->tag_name }}</span>
              @endforeach
            @else
              <span class="text-muted">No tags</span>
            @endif
          </div>
        </div>

        <div class="row mb-4 pb-3 border-bottom">
          <div class="col-sm-4 text-muted">Customer Since</div>
          <div class="col-sm-8 fw-bold">{{ $contact->created_at->format('d M Y, h:i A') }}</div>
        </div>

        @if(auth()->id() === 1)
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm btn-icon-text" data-bs-toggle="modal" data-bs-target="#editContactModal">
            <i data-lucide="user" class="btn-icon-prepend icon-sm"></i> Edit Contact
          </button>
          <button class="btn btn-outline-secondary btn-sm btn-icon-text" data-bs-toggle="modal" data-bs-target="#tagsModal">
            <i data-lucide="tag" class="btn-icon-prepend icon-sm"></i> Add Tag
          </button>
          <button class="btn btn-outline-danger btn-sm btn-icon-text" data-bs-toggle="modal" data-bs-target="#tagsModal">
            <i data-lucide="tag" class="btn-icon-prepend icon-sm"></i> Remove Tag
          </button>
        </div>
        @endif
      </div>
    </div>

    <!-- SECTION 6: LOCATION -->
    <div class="card w-100 grid-margin">
      <div class="card-body">
        <h6 class="card-title text-muted mb-1">SECTION 6: LOCATION</h6>
        <p class="text-muted tx-13 mb-4">Contact location details</p>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted small mb-1">Country</div>
            <div class="fw-semibold">{{ $contact->country ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">State</div>
            <div class="fw-semibold">{{ $contact->state ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">City</div>
            <div class="fw-semibold">{{ $contact->city ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Pincode</div>
            <div class="fw-semibold">{{ $contact->pincode ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SECTION 3: ACTIVITY TIMELINE -->
  <div class="col-md-6 col-lg-5 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title text-muted mb-0">SECTION 3: ACTIVITY TIMELINE</h6>
          <button class="btn btn-sm btn-outline-light border text-dark fw-bold d-flex align-items-center">
            <i data-lucide="calendar" class="icon-sm me-2 text-muted"></i> {{ now()->format('M j, Y') }}
          </button>
        </div>

        <div class="border rounded p-4 mb-2">
          <div style="border-left: 1px solid #e9ecef; padding-left: 24px; position: relative; margin-left: 5px;">
            
            @forelse($contact->conversations->take(5) as $conv)
              @php
                  $dotColor = 'bg-secondary';
                  if ($conv->status === 'OPEN') $dotColor = 'bg-info';
                  elseif ($conv->status === 'RESOLVED') $dotColor = 'bg-success';
              @endphp
              <div class="mb-4 w-100" style="position: relative;">
                <span class="{{ $dotColor }} rounded-circle" style="width: 12px; height: 12px; position: absolute; left: -31px; top: 4px; border: 2px solid white; box-shadow: 0 0 0 1px #e9ecef;"></span>
                <div class="d-flex justify-content-between align-items-start w-100">
                  <h6 class="mb-1 fw-bold text-dark" style="font-size: 14.5px;">Conversation {{ ucfirst(strtolower($conv->status)) }}</h6>
                  <span class="text-muted text-nowrap ms-2" style="font-size: 11px;">{{ $conv->created_at->format('h:i A') }}</span>
                </div>
                <p class="text-muted mb-1 text-wrap" style="font-size: 13px;">CONV-{{ str_pad($conv->id, 5, '0', STR_PAD_LEFT) }} — {{ $conv->title ?? 'No Title' }}</p>
                <p class="text-muted mb-0" style="font-size: 12px;">by <span class="text-secondary">{{ $conv->assignedUser->name ?? 'System' }}</span></p>
              </div>
            @empty
              <p class="text-muted tx-13">No recent activity.</p>
            @endforelse

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SECTION 4: CONTACT STATISTICS & HISTORY -->
<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <h6 class="card-title text-muted mb-1">SECTION 4: CONTACT STATISTICS</h6>
        <p class="text-muted tx-13 mb-4">Overview of conversations and engagement for this contact</p>
        
        <div class="row g-3 mb-4">
          <div class="col-md-2">
            <div class="p-3 rounded bg-primary-subtle border border-primary-subtle h-100">
              <p class="text-muted tx-12 fw-bold mb-2 text-uppercase">TOTAL CONVERSATIONS</p>
              <h3 class="fw-bold text-dark mb-0">{{ $contact->conversations_count }}</h3>
            </div>
          </div>
          <div class="col-md-2">
            <div class="p-3 rounded bg-info-subtle border border-info-subtle h-100">
              <p class="text-info tx-12 fw-bold mb-2 text-uppercase">TOTAL MESSAGES</p>
              <h3 class="fw-bold text-info mb-0">{{ $contact->messages_count }}</h3>
            </div>
          </div>
          <div class="col-md-2">
            <div class="p-3 rounded bg-success-subtle border border-success-subtle h-100">
              <p class="text-muted tx-12 fw-bold mb-2 text-uppercase">LAST CONTACTED</p>
              <h5 class="fw-bold text-success mb-0">{{ $contact->conversations->first() ? $contact->conversations->first()->last_message_at ? \Carbon\Carbon::parse($contact->conversations->first()->last_message_at)->diffForHumans() : '-' : '-' }}</h5>
            </div>
          </div>
          <div class="col-md-2">
            <div class="p-3 rounded bg-warning-subtle border border-warning-subtle h-100">
              <p class="text-muted tx-12 fw-bold mb-2 text-uppercase">FIRST CONTACTED</p>
              <h6 class="fw-bold text-warning mb-0">{{ $contact->created_at->format('d M Y, h:i A') }}</h6>
            </div>
          </div>
          <div class="col-md-2">
            <div class="p-3 rounded bg-primary-subtle border border-primary-subtle h-100" style="background-color: #f3f0ff !important; border-color: #e5defa !important;">
              <p class="text-muted tx-12 fw-bold mb-2 text-uppercase">OPEN CONVERSATIONS</p>
              <h3 class="fw-bold mb-0" style="color: #6c5ce7;">{{ $contact->conversations->where('status', 'OPEN')->count() }}</h3>
            </div>
          </div>
          <div class="col-md-2">
            <div class="p-3 rounded bg-danger-subtle border border-danger-subtle h-100">
              <p class="text-muted tx-12 fw-bold mb-2 text-uppercase">RESOLVED CONVERSATIONS</p>
              <h3 class="fw-bold text-danger mb-0">{{ $contact->conversations->where('status', 'RESOLVED')->count() }}</h3>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center mb-3">
          <h5 class="mb-0 fw-bold me-2">Conversation History</h5>
          <span class="badge bg-secondary rounded-pill">{{ $contact->conversations_count }}</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="bg-light">
              <tr>
                <th class="pt-2 pb-2">CONVERSATION NO.</th>
                <th class="pt-2 pb-2">TITLE</th>
                <th class="pt-2 pb-2">ASSIGNED TO</th>
                <th class="pt-2 pb-2">STATUS</th>
                <th class="pt-2 pb-2">STARTED</th>
                <th class="pt-2 pb-2">LAST ACTIVITY</th>
                <th class="pt-2 pb-2 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($contact->conversations as $conv)
                <tr>
                  <td class="fw-bold text-dark">#CONV-{{ str_pad($conv->id, 5, '0', STR_PAD_LEFT) }}</td>
                  <td>{{ $conv->title ?? 'No Title' }}</td>
                  <td>
                    @if($conv->assignedUser)
                      <span class="badge bg-light text-dark border rounded-pill me-1">{{ substr($conv->assignedUser->name, 0, 2) }}</span> {{ $conv->assignedUser->name }}
                    @else
                      -
                    @endif
                  </td>
                  <td>
                    @if($conv->status === 'OPEN')
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Open</span>
                    @elseif($conv->status === 'RESOLVED')
                      <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Resolved</span>
                    @elseif($conv->status === 'CLOSED')
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3">Closed</span>
                    @else
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">{{ $conv->status }}</span>
                    @endif
                  </td>
                  <td>{{ $conv->created_at->format('M d, Y') }}</td>
                  <td>{{ $conv->last_message_at ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans() : '-' }}</td>
                  <td class="text-center">
                    <a href="{{ route('conversations.show', $conv->id) }}" class="btn btn-sm btn-light btn-icon border"><i data-lucide="eye" class="icon-sm"></i></a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">No conversations found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3 align-items-center">
          <div class="text-muted small">{{ $contact->conversations_count }} total conversation(s)</div>
          <div>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item disabled"><a class="page-link" href="#"><i data-lucide="chevron-left" class="icon-sm"></i></a></li>
              <li class="page-item active"><a class="page-link" href="#">1</a></li>
              <li class="page-item"><a class="page-link" href="#">2</a></li>
              <li class="page-item"><a class="page-link" href="#"><i data-lucide="chevron-right" class="icon-sm"></i></a></li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- SECTION 4: FILES SHARED & SECTION 5: TAGS -->
<div class="row">
  <div class="col-md-8 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <h6 class="card-title text-muted mb-1">SECTION 4: FILES SHARED</h6>
        <p class="text-muted tx-13 mb-4">Preview & download any shared file</p>
        
        <ul class="nav nav-tabs nav-tabs-line" id="filesTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active d-flex align-items-center" id="images-tab" data-bs-toggle="tab" href="#images" role="tab" aria-selected="true">
              <i data-lucide="image" class="icon-sm me-2"></i> Images <span class="badge bg-light text-dark ms-2 rounded-pill">3</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" id="docs-tab" data-bs-toggle="tab" href="#docs" role="tab" aria-selected="false">
              <i data-lucide="file-text" class="icon-sm me-2"></i> Documents <span class="badge bg-light text-dark ms-2 rounded-pill">2</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" id="video-tab" data-bs-toggle="tab" href="#video" role="tab" aria-selected="false">
              <i data-lucide="video" class="icon-sm me-2"></i> Videos <span class="badge bg-light text-dark ms-2 rounded-pill">2</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" id="audio-tab" data-bs-toggle="tab" href="#audio" role="tab" aria-selected="false">
              <i data-lucide="music" class="icon-sm me-2"></i> Audio <span class="badge bg-light text-dark ms-2 rounded-pill">2</span>
            </a>
          </li>
        </ul>
        
        <div class="tab-content border border-top-0 p-3 rounded-bottom" id="filesTabContent">
          <div class="tab-pane fade show active" id="images" role="tabpanel" aria-labelledby="images-tab">
            
            <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2">
              <div class="d-flex align-items-center">
                <div class="wd-40 ht-40 rounded bg-light d-flex align-items-center justify-content-center me-3 border">
                  <i data-lucide="image" class="icon-md text-muted"></i>
                </div>
                <div>
                  <h6 class="mb-1 text-dark fw-bold">profile-photo.jpg</h6>
                  <p class="text-muted tx-12 mb-0">1.4 MB <span class="mx-1">•</span> by Jenny Wilson <span class="mx-1">•</span> Aug 3, 2026 09:15 AM</p>
                </div>
              </div>
              <div>
                <button class="btn btn-sm btn-outline-secondary btn-icon-text me-2"><i data-lucide="eye" class="btn-icon-prepend icon-sm"></i> Preview</button>
                <button class="btn btn-sm btn-outline-secondary btn-icon-text"><i data-lucide="download" class="btn-icon-prepend icon-sm"></i> Download</button>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2">
              <div class="d-flex align-items-center">
                <div class="wd-40 ht-40 rounded bg-light d-flex align-items-center justify-content-center me-3 border">
                  <i data-lucide="image" class="icon-md text-muted"></i>
                </div>
                <div>
                  <h6 class="mb-1 text-dark fw-bold">id-front-scan.png</h6>
                  <p class="text-muted tx-12 mb-0">2.1 MB <span class="mx-1">•</span> by Jenny Wilson <span class="mx-1">•</span> Aug 3, 2026 09:20 AM</p>
                </div>
              </div>
              <div>
                <button class="btn btn-sm btn-outline-secondary btn-icon-text me-2"><i data-lucide="eye" class="btn-icon-prepend icon-sm"></i> Preview</button>
                <button class="btn btn-sm btn-outline-secondary btn-icon-text"><i data-lucide="download" class="btn-icon-prepend icon-sm"></i> Download</button>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 border rounded">
              <div class="d-flex align-items-center">
                <div class="wd-40 ht-40 rounded bg-light d-flex align-items-center justify-content-center me-3 border">
                  <i data-lucide="image" class="icon-md text-muted"></i>
                </div>
                <div>
                  <h6 class="mb-1 text-dark fw-bold">signature-scan.jpg</h6>
                  <p class="text-muted tx-12 mb-0">540 KB <span class="mx-1">•</span> by Sarah Kim <span class="mx-1">•</span> Aug 4, 2026 10:00 AM</p>
                </div>
              </div>
              <div>
                <button class="btn btn-sm btn-outline-secondary btn-icon-text me-2"><i data-lucide="eye" class="btn-icon-prepend icon-sm"></i> Preview</button>
                <button class="btn btn-sm btn-outline-secondary btn-icon-text"><i data-lucide="download" class="btn-icon-prepend icon-sm"></i> Download</button>
              </div>
            </div>

          </div>
          <div class="tab-pane fade" id="docs" role="tabpanel" aria-labelledby="docs-tab">
            <p class="text-muted text-center py-4">No documents available.</p>
          </div>
          <div class="tab-pane fade" id="video" role="tabpanel" aria-labelledby="video-tab">
             <p class="text-muted text-center py-4">No videos available.</p>
          </div>
          <div class="tab-pane fade" id="audio" role="tabpanel" aria-labelledby="audio-tab">
             <p class="text-muted text-center py-4">No audio files available.</p>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="col-md-4 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <h6 class="card-title text-muted mb-1">SECTION 5: TAGS</h6>
        <p class="text-muted tx-13 mb-4">Manage contact tags</p>
        
        <div class="border rounded p-4 text-center mb-3 min-vh-25 d-flex flex-column justify-content-center bg-light">
          @if($contact->tags->count() > 0)
            <div class="d-flex flex-wrap gap-2 justify-content-center">
              @foreach($contact->tags as $tag)
                <span class="badge border text-dark bg-white py-2 px-3 shadow-sm" style="font-size: 13px;"><i data-lucide="tag" class="icon-sm me-1 text-primary"></i> {{ $tag->tag_name }}</span>
              @endforeach
            </div>
          @else
            <p class="text-muted mb-0">No tags assigned yet</p>
          @endif
        </div>

        @if(auth()->id() === 1)
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-icon-text" data-bs-toggle="modal" data-bs-target="#tagsModal">
            <i data-lucide="tag" class="btn-icon-prepend icon-sm"></i> Add Tag
          </button>
          <button class="btn btn-outline-danger btn-icon-text" data-bs-toggle="modal" data-bs-target="#tagsModal">
            <i data-lucide="tag" class="btn-icon-prepend icon-sm"></i> Remove Tag
          </button>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>



<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1" aria-labelledby="editContactModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editContactModalLabel">Edit Contact</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
      </div>
      <form action="{{ route('contacts.update', $contact->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label for="custom_name" class="form-label text-muted">Custom Name</label>
            <input type="text" class="form-control" id="custom_name" name="custom_name" value="{{ $contact->custom_name ?? $contact->whatsapp_profile_name }}">
          </div>
          <div class="mb-3">
            <label for="whatsapp_profile_name" class="form-label text-muted">WhatsApp Profile Name</label>
            <input type="text" class="form-control" id="whatsapp_profile_name" name="whatsapp_profile_name" value="{{ $contact->whatsapp_profile_name }}">
          </div>
          <div class="mb-3">
            <label for="phone_number" class="form-label text-muted">Phone Number</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ $contact->phone_number }}">
          </div>
          <div class="mb-3">
            <label for="email" class="form-label text-muted">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ $contact->email }}">
          </div>
          <div class="mb-3">
            <label for="whatsapp_phone_number_id" class="form-label text-muted">WhatsApp phonenumberid</label>
            <input type="text" class="form-control" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" value="{{ $contact->whatsapp_phone_number_id }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="country" class="form-label text-muted">Country</label>
              <select class="form-select" id="country" name="country">
                <option value="">Select Country</option>
                @foreach($countries as $c)
                  <option value="{{ $c->name }}" {{ $contact->country == $c->name ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="state" class="form-label text-muted">State</label>
              <select class="form-select" id="state" name="state">
                <option value="">Select State</option>
                <!-- States will be populated dynamically -->
                @if($contact->state)
                  <option value="{{ $contact->state }}" selected>{{ $contact->state }}</option>
                @endif
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="city" class="form-label text-muted">City</label>
              <input type="text" class="form-control" id="city" name="city" value="{{ $contact->city }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="pincode" class="form-label text-muted">Pincode</label>
              <input type="text" class="form-control" id="pincode" name="pincode" value="{{ $contact->pincode }}">
            </div>
          </div>
          <div class="mb-3">
            <label for="customer_since" class="form-label text-muted">Customer Since</label>
            <input type="text" class="form-control text-muted" id="customer_since" value="{{ $contact->created_at->format('d M Y, h:i A') }}" readonly disabled>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add / Remove Tags Modal -->
<div class="modal fade" id="tagsModal" tabindex="-1" aria-labelledby="tagsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="tagsModalLabel">Add / Remove Tags</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
      </div>
      <form action="{{ route('contacts.updateTags', $contact->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div id="hiddenTagsInputs"></div>
        
        <div class="modal-body pb-0">
          
          <div class="mb-4">
            <label class="form-label text-muted fw-bold">Selected Tags</label>
            <div id="selectedTagsContainer" class="form-control d-flex flex-wrap gap-2 align-items-center" style="min-height: 42px;">
              <!-- Badges will be rendered here via JS -->
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label text-muted fw-bold">Available Tags</label>
            <div class="d-flex flex-wrap gap-2" id="availableTagsContainer">
              @foreach($allTags as $tag)
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill available-tag-btn" data-tag-id="{{ $tag->id }}" data-tag-name="{{ $tag->tag_name }}">
                  + {{ $tag->tag_name }}
                </button>
              @endforeach
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label text-muted fw-bold">Add Custom Tag</label>
            <div class="input-group">
              <input type="text" class="form-control" id="customTagInput" placeholder="Enter tag name...">
              <button class="btn btn-outline-secondary" type="button" id="addCustomTagBtn">
                <i data-lucide="plus" class="icon-sm"></i>
              </button>
            </div>
          </div>

        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Tags</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('build/plugins/flatpickr/flatpickr.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    'use strict';

    // State management
    let selectedTags = [
      @foreach($contact->tags as $tag)
        { id: "{{ $tag->id }}", name: "{{ $tag->tag_name }}" },
      @endforeach
    ];

    const colors = [
      { bg: 'bg-warning-subtle', text: 'text-warning' }, // yellow
      { bg: 'bg-danger-subtle', text: 'text-danger' },   // red
      { bg: 'bg-info-subtle', text: 'text-info' },       // blue
      { bg: 'bg-success-subtle', text: 'text-success' }, // green
      { bg: 'bg-primary-subtle', text: 'text-primary' }, // purple
    ];

    function getColor(index) {
      return colors[index % colors.length];
    }

    function renderTags() {
      let container = $('#selectedTagsContainer');
      let hiddenInputs = $('#hiddenTagsInputs');
      container.empty();
      hiddenInputs.empty();

      if (selectedTags.length === 0) {
        // container.html('<span class="text-muted" style="font-size: 14px;">No tags selected</span>');
      }

      selectedTags.forEach((tag, index) => {
        let color = getColor(index);
        
        // Render badge in container
        let badge = $(`
          <span class="badge ${color.bg} ${color.text} rounded-pill d-flex align-items-center" style="font-size: 13px; font-weight: 500; padding: 6px 10px;">
            ${tag.name} 
            <i data-lucide="x" class="ms-1 remove-tag-icon" style="cursor: pointer; width: 14px; height: 14px;" data-tag-id="${tag.id}"></i>
          </span>
        `);
        container.append(badge);

        // Add hidden input for form submission
        hiddenInputs.append(`<input type="hidden" name="tags[]" value="${tag.id}">`);
      });

      // Update available tags buttons
      $('.available-tag-btn').each(function() {
        let btn = $(this);
        let btnId = btn.data('tag-id').toString();
        let btnName = btn.data('tag-name');
        
        // Find if this available tag is in selectedTags
        let indexInSelected = selectedTags.findIndex(t => t.id.toString() === btnId);

        if (indexInSelected !== -1) {
          let color = getColor(indexInSelected);
          btn.removeClass('btn-outline-secondary').addClass(`${color.bg} ${color.text}`);
          btn.html(`✓ ${btnName}`);
        } else {
          // Reset to default
          btn.removeClass().addClass('btn btn-sm btn-outline-secondary rounded-pill available-tag-btn');
          btn.html(`+ ${btnName}`);
        }
      });

      // Re-initialize lucide icons for newly added 'x' icons
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    }

    // Toggle available tag click
    $('.available-tag-btn').on('click', function() {
      let tagId = $(this).data('tag-id').toString();
      let tagName = $(this).data('tag-name');
      
      let index = selectedTags.findIndex(t => t.id.toString() === tagId);
      if (index !== -1) {
        selectedTags.splice(index, 1); // Remove
      } else {
        selectedTags.push({ id: tagId, name: tagName }); // Add
      }
      renderTags();
    });

    // Remove from selected tags container (the 'x' icon)
    $(document).on('click', '.remove-tag-icon', function() {
      let tagId = $(this).data('tag-id').toString();
      selectedTags = selectedTags.filter(t => t.id.toString() !== tagId);
      renderTags();
    });

    // Add custom tag
    $('#addCustomTagBtn').on('click', function() {
      let customTag = $('#customTagInput').val().trim();
      if (customTag !== '') {
        // Check if already exists in selected
        if (!selectedTags.find(t => t.name.toLowerCase() === customTag.toLowerCase())) {
          // We use the tag name as ID for custom tags. 
          // The backend controller handles non-numeric IDs as custom tags.
          selectedTags.push({ id: customTag, name: customTag });
          renderTags();
        }
        $('#customTagInput').val(''); // clear input
      }
    });

    // Also trigger add custom tag on Enter key in input
    $('#customTagInput').on('keypress', function(e) {
      if (e.which === 13) {
        e.preventDefault();
        $('#addCustomTagBtn').click();
      }
    });

    // Initial render
    renderTags();

    // Dynamic Country/State Dropdown
    $('#country').on('change', function() {
      let countryName = $(this).val();
      let stateSelect = $('#state');
      
      stateSelect.empty();
      stateSelect.append('<option value="">Select State</option>');

      if (countryName) {
        $.ajax({
          url: '/api/states/' + encodeURIComponent(countryName),
          type: 'GET',
          success: function(data) {
            $.each(data, function(key, value) {
              stateSelect.append('<option value="' + value.name + '">' + value.name + '</option>');
            });
          }
        });
      }
    });

  });
</script>
@endpush
