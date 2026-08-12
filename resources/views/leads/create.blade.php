@extends('layout.master')

@section('title', 'Add Lead')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Add Lead</h4>
  </div>
  <div>
    <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="arrow-left"></i>
      Back
    </a>
  </div>
</div>

<form action="{{ route('leads.store') }}" method="POST" novalidate>
  @csrf
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
                value="{{ old('lead_name') }}" placeholder="Full name" autofocus>
              @error('lead_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="company_name" class="form-label">Company Name</label>
              <input type="text" id="company_name" name="company_name"
                class="form-control @error('company_name') is-invalid @enderror"
                value="{{ old('company_name') }}" placeholder="Optional">
              @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="text" id="phone_number" name="phone_number"
                class="form-control @error('phone_number') is-invalid @enderror"
                value="{{ old('phone_number') }}" placeholder="+91 99999 99999">
              @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="whatsapp_number" class="form-label">
                WhatsApp Number
                <!-- <small class="text-muted">(leave blank to use phone)</small> -->
              </label>
              <div class="input-group">
                <input type="text" id="whatsapp_number" name="whatsapp_number"
                  class="form-control @error('whatsapp_number') is-invalid @enderror"
                  value="{{ old('whatsapp_number') }}" placeholder="Same as phone?">
                <button class="btn btn-outline-secondary" type="button" id="copyPhone" tabindex="-1"
                  title="Copy from phone">
                  <i data-lucide="copy" class="icon-sm"></i>
                </button>
                @error('whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" placeholder="Optional">
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="lead_source_id" class="form-label">Lead Source <span class="text-danger">*</span></label>
              <select id="lead_source_id" name="lead_source_id"
                class="form-select @error('lead_source_id') is-invalid @enderror">
                <option value="">— Select Source —</option>
                @foreach($leadSources as $source)
                  <option value="{{ $source->id }}" {{ old('lead_source_id') == $source->id ? 'selected' : '' }}>
                    {{ $source->name }}
                  </option>
                @endforeach
              </select>
              @error('lead_source_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              @if($leadSources->isEmpty())
                <small class="text-warning">No active lead sources found. <a href="{{ route('lead-source.create') }}">Add one first.</a></small>
              @endif
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
            class="form-control @error('requirement') is-invalid @enderror"
            placeholder="e.g. GPS for 50 trucks">{{ old('requirement') }}</textarea>
          @error('requirement')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Notes --}}
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0">Notes / Description <span class="text-danger">*</span></h6>
        </div>
        <div class="card-body">
          <textarea id="note" name="note" rows="5"
            class="form-control @error('note') is-invalid @enderror"
            placeholder="e.g. Called at 3 PM. Looking for GPS for 120 buses. Asked for quotation.">{{ old('note') }}</textarea>
          @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
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
              <option value="{{ $item->id }}" {{ old('interested_in_id') == $item->id ? 'selected' : '' }}>
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
              <option value="{{ $ls->id }}" {{ old('lead_status_id') == $ls->id ? 'selected' : '' }}>
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
              value="{{ old('follow_up_date') }}">
            @error('follow_up_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label for="follow_up_time" class="form-label">Time</label>
            <input type="time" id="follow_up_time" name="follow_up_time"
              class="form-control @error('follow_up_time') is-invalid @enderror"
              value="{{ old('follow_up_time') }}">
            @error('follow_up_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="follow_up_note" class="form-label">Next Action</label>
            <textarea id="follow_up_note" name="follow_up_note" rows="4"
              class="form-control @error('follow_up_note') is-invalid @enderror"
              placeholder="Example:&#10;- Send quotation&#10;- Demo&#10;- Call back&#10;- Meeting&#10;- Payment Reminder">{{ old('follow_up_note') }}</textarea>
            @error('follow_up_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100">
        <i data-lucide="save" class="icon-sm me-1"></i> Save Lead
      </button>
      <button type="submit" name="save_and_add_another" value="1" class="btn btn-info w-100 mt-2 text-white">
        <i data-lucide="plus-circle" class="icon-sm me-1"></i> Save & Add Another
      </button>
      <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>

    </div>
  </div>
</form>

@endsection

@push('custom-scripts')
<script>
  lucide.createIcons();

  // Copy phone → whatsapp
  document.getElementById('copyPhone').addEventListener('click', function () {
    const phone = document.getElementById('phone_number').value.trim();
    if (phone) {
      document.getElementById('whatsapp_number').value = phone;
    }
  });
</script>
@endpush
