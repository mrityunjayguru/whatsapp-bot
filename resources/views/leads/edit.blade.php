@extends('layout.master')

@section('title', 'Edit Lead — ' . $lead->lead_id)

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <div class="d-flex align-items-center gap-2">
      <h4 class="mb-0">Edit Lead</h4>
      <span class="badge bg-secondary fs-6">{{ $lead->lead_id }}</span>
    </div>
  </div>
  <div>
    <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="arrow-left"></i>
      Back
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<form action="{{ route('leads.update', $lead) }}" method="POST" novalidate>
  @csrf
  @method('PUT')

  <div class="row">

    {{-- LEFT COLUMN --}}
    <div class="col-lg-8">

      {{-- Basic Information --}}
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0">Basic Information</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label for="lead_name" class="form-label">Lead Name <span class="text-danger">*</span></label>
              <input type="text" id="lead_name" name="lead_name"
                class="form-control @error('lead_name') is-invalid @enderror"
                value="{{ old('lead_name', $lead->lead_name) }}" autofocus>
              @error('lead_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="company_name" class="form-label">Company Name</label>
              <input type="text" id="company_name" name="company_name"
                class="form-control @error('company_name') is-invalid @enderror"
                value="{{ old('company_name', $lead->company_name) }}" placeholder="Optional">
              @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="text" id="phone_number" name="phone_number"
                class="form-control @error('phone_number') is-invalid @enderror"
                value="{{ old('phone_number', $lead->phone_number) }}">
              @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="whatsapp_number" class="form-label">WhatsApp Number</label>
              <div class="input-group">
                <input type="text" id="whatsapp_number" name="whatsapp_number"
                  class="form-control @error('whatsapp_number') is-invalid @enderror"
                  value="{{ old('whatsapp_number', $lead->whatsapp_number) }}">
                <button class="btn btn-outline-secondary" type="button" id="copyPhone" tabindex="-1" title="Copy from phone">
                  <i data-lucide="copy" class="icon-sm"></i>
                </button>
                @error('whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $lead->email) }}" placeholder="Optional">
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="lead_source_id" class="form-label">Lead Source <span class="text-danger">*</span></label>
              <select id="lead_source_id" name="lead_source_id"
                class="form-select @error('lead_source_id') is-invalid @enderror">
                <option value="">— Select Source —</option>
                @foreach($leadSources as $source)
                  <option value="{{ $source->id }}"
                    {{ old('lead_source_id', $lead->lead_source_id) == $source->id ? 'selected' : '' }}>
                    {{ $source->name }}
                  </option>
                @endforeach
              </select>
              @error('lead_source_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

          </div>
        </div>
      </div>

      {{-- Requirement --}}
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0">Requirement <span class="text-danger">*</span></h6>
        </div>
        <div class="card-body">
          <textarea id="requirement" name="requirement" rows="3"
            class="form-control @error('requirement') is-invalid @enderror">{{ old('requirement', $lead->requirement) }}</textarea>
          @error('requirement')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Notes / Description — hidden for now --}}
      {{-- <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0">Notes / Description</h6>
        </div>
        <div class="card-body">
          <textarea id="note" name="note" rows="5"
            class="form-control @error('note') is-invalid @enderror"
            placeholder="Enter notes...">{{ old('note', $notes->first()?->note) }}</textarea>
          @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div> --}}

      {{-- Activity Timeline — full width in left column --}}
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i data-lucide="calendar-clock" class="icon-sm me-1"></i>
            Activity Timeline
          </h6>
          @if($followUps->count())
            <span class="badge bg-secondary">{{ $followUps->count() }}</span>
          @endif
        </div>
        @if($followUps->count())
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @php
              $ordered = $followUps->sortByDesc('created_at')->values();
              $total   = $ordered->count();
            @endphp
            @foreach($ordered as $i => $fu)
            @php
              $isLatest = $i === 0;
              $label    = $isLatest ? 'Next Follow-up' : 'Follow-up ' . ($total - $i);
            @endphp
            <li class="list-group-item px-4 py-3">
            <div class="mb-2 d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">{{ $fu->follow_up_date->format('d M') }}</div>
                @if($fu->follow_up_time)
                  <div class="text-muted small">{{ \Carbon\Carbon::parse($fu->follow_up_time)->format('h:i A') }}</div>
                @endif
              </div>
              @if($isLatest)
                <span class="badge bg-primary">{{ $label }}</span>
              @else
                <span class="badge bg-secondary">{{ $label }}</span>
              @endif
            </div>
            @if($fu->note)
              <div class="text-dark small" style="line-height: 1.6;">
                {!! nl2br(e($fu->note)) !!}
              </div>
            @endif
          </li>
            @endforeach
          </ul>
        </div>
        @else
        <div class="card-body">
          <p class="text-muted mb-0 small">No activity timeline yet.</p>
        </div>
        @endif
      </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-lg-4">

      {{-- Interested In --}}
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0">Interested In</h6>
        </div>
        <div class="card-body">
          <select id="interested_in_id" name="interested_in_id"
            class="form-select @error('interested_in_id') is-invalid @enderror">
            <option value="">— Select Interest —</option>
            @foreach($interestedIns as $item)
              <option value="{{ $item->id }}"
                {{ old('interested_in_id', $lead->interested_in_id) == $item->id ? 'selected' : '' }}>
                {{ $item->title }}
              </option>
            @endforeach
          </select>
          @error('interested_in_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Status --}}
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0">Status <span class="text-danger">*</span></h6>
        </div>
        <div class="card-body">
          <select id="lead_status_id" name="lead_status_id"
            class="form-select @error('lead_status_id') is-invalid @enderror">
            <option value="">— Select Status —</option>
            @foreach($leadStatuses as $ls)
              <option value="{{ $ls->id }}"
                {{ old('lead_status_id', $lead->lead_status_id) == $ls->id ? 'selected' : '' }}>
                {{ $ls->name }}
              </option>
            @endforeach
          </select>
          @error('lead_status_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          @if($leadStatuses->isEmpty())
            <small class="text-warning">No active statuses found. <a href="{{ route('lead-status.create') }}">Add one first.</a></small>
          @endif
        </div>
      </div>

      {{-- Next Follow-up --}}
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0">Next Follow-up</h6>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="follow_up_date" class="form-label">Date</label>
            <input type="date" id="follow_up_date" name="follow_up_date"
              class="form-control @error('follow_up_date') is-invalid @enderror"
              value="{{ old('follow_up_date', $lead->follow_up_date?->format('Y-m-d')) }}">
            @error('follow_up_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label for="follow_up_time" class="form-label">Time</label>
            <input type="time" id="follow_up_time" name="follow_up_time"
              class="form-control @error('follow_up_time') is-invalid @enderror"
              value="{{ old('follow_up_time', $lead->follow_up_time) }}">
            @error('follow_up_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="follow_up_note" class="form-label">Next Action</label>
            <textarea id="follow_up_note" name="follow_up_note" rows="4"
              class="form-control @error('follow_up_note') is-invalid @enderror"
              placeholder="Example:&#10;- Send quotation&#10;- Demo&#10;- Call back&#10;- Meeting&#10;- Payment Reminder">{{ old('follow_up_note', $followUps->sortByDesc('created_at')->first()?->note) }}</textarea>
            @error('follow_up_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>

      {{-- Buttons --}}
      @if(!$lead->is_closed)
      <button type="submit" class="btn btn-primary w-100">
        <i data-lucide="save" class="icon-sm me-1"></i> Update Lead
      </button>
      <button type="button" class="btn btn-outline-danger mt-2 w-100" id="closeLeadBtn">
        <i data-lucide="x-circle" class="icon-sm me-1"></i> Close Lead
      </button>
      @else
      <div class="alert alert-secondary text-center py-2 mb-0">
        <i data-lucide="lock" class="icon-sm me-1"></i> This lead is closed
      </div>
      @endif

      <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>

    </div>
  </div>
</form>

{{-- Close Lead form — outside main form to avoid nested form issue --}}
@if(!$lead->is_closed)
<form action="{{ route('leads.close', $lead) }}" method="POST" id="closeLeadForm">
  @csrf
  @method('PATCH')
</form>
@endif
@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  lucide.createIcons();

  document.getElementById('copyPhone').addEventListener('click', function () {
    const phone = document.getElementById('phone_number').value.trim();
    if (phone) {
      document.getElementById('whatsapp_number').value = phone;
    }
  });

  const closeBtn = document.getElementById('closeLeadBtn');
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      Swal.fire({
        title: 'Close this lead?',
        text: 'Once closed, the lead cannot be edited further.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Close Lead',
        cancelButtonText: 'Cancel',
      }).then(function (result) {
        if (result.isConfirmed) {
          document.getElementById('closeLeadForm').submit();
        }
      });
    });
  }
</script>
@endpush
