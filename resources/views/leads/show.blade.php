@extends('layout.master')

@section('title', 'Lead — ' . $lead->lead_id)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <div class="d-flex align-items-center gap-2">
      <h4 class="mb-0">Lead Details</h4>
      <span class="badge bg-secondary fs-6">{{ $lead->lead_id }}</span>
      @if($lead->is_closed)
        <span class="badge bg-danger">Closed</span>
      @endif
    </div>
  </div>
  <div class="d-flex gap-2">
    @if(auth()->user()->hasPermission('leads.edit') && !$lead->is_closed)
    <a href="{{ route('leads.edit', $lead) }}" class="btn btn-primary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="edit-2"></i>
      Edit
    </a>
    @endif
    <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="arrow-left"></i>
      Back
    </a>
  </div>
</div>

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
            <div class="text-muted small mb-1">Lead Name</div>
            <div class="fw-semibold">{{ $lead->lead_name }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Company Name</div>
            <div>{{ $lead->company_name ?: '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Phone Number</div>
            <div>
              <a href="tel:{{ $lead->phone_number }}" class="text-decoration-none">
                <i data-lucide="phone" class="icon-sm me-1 text-success"></i>{{ $lead->phone_number }}
              </a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">WhatsApp Number</div>
            <div>
              @if($lead->whatsapp_number)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lead->whatsapp_number) }}"
                   target="_blank" class="text-decoration-none text-success">
                  <i data-lucide="message-circle" class="icon-sm me-1"></i>{{ $lead->whatsapp_number }}
                </a>
              @else
                —
              @endif
            </div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Email</div>
            <div>
              @if($lead->email)
                <a href="mailto:{{ $lead->email }}" class="text-decoration-none">
                  <i data-lucide="mail" class="icon-sm me-1"></i>{{ $lead->email }}
                </a>
              @else
                —
              @endif
            </div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Lead Source</div>
            <div>{{ $lead->leadSource->name ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Requirement --}}
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Requirement</h6>
      </div>
      <div class="card-body">
        <p class="mb-0" style="white-space:pre-wrap;">{{ $lead->requirement }}</p>
      </div>
    </div>

    {{-- Activity Timeline --}}
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

    {{-- Summary Stats --}}
    <div class="card mb-4">
      <div class="card-body">
        <div class="row text-center">
          <div class="col-6 mb-3">
            <div class="text-muted small">Created</div>
            <div class="fw-bold fs-5">{{ $lead->created_at->format('d M') }}</div>
          </div>
          <div class="col-6 mb-3">
            <div class="text-muted small">Last Contact</div>
            @php
              $lastContactDate = $followUps->first()?->created_at ?? $lead->created_at;
              if ($lastContactDate->isToday()) {
                  $lastContact = 'Today';
              } elseif ($lastContactDate->isYesterday()) {
                  $lastContact = 'Yesterday';
              } else {
                  $lastContact = $lastContactDate->format('d M');
              }
            @endphp
            <div class="fw-bold fs-5">{{ $lastContact }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Follow-ups</div>
            <div class="fw-bold fs-5">{{ $followUps->count() }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Age</div>
            <div class="fw-bold fs-5">{{ (int) $lead->created_at->diffInDays(now()) }} Days</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Status --}}
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Status</h6>
      </div>
      <div class="card-body">
        @php
          $statusName = $lead->leadStatus->name ?? null;
          $dotColors  = [
            'new'        => '#3b82f6',
            'contacted'  => '#6b7280',
            'interested' => '#f97316',
            'follow-up'  => '#f59e0b',
            'follow up'  => '#f59e0b',
            'quotation sent' => '#8b5cf6',
            'won'        => '#22c55e',
            'lost'       => '#ef4444',
            'not interested' => '#1f2937',
          ];
          $bgColor = $dotColors[strtolower($statusName ?? '')] ?? '#6b7280';
        @endphp
        @if($statusName)
          <span style="display:inline-block; padding:4px 14px; border-radius:4px; font-size:13px; font-weight:600; color:#fff; background:{{ $bgColor }};">
            {{ $statusName }}
          </span>
        @else
          <span class="text-muted">—</span>
        @endif
      </div>
    </div>

    {{-- Next Follow-up --}}
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Next Follow-up</h6>
      </div>
      <div class="card-body">
        @if($lead->follow_up_date)
          <div class="d-flex align-items-center gap-2 mb-2">
            <i data-lucide="calendar" class="icon-sm text-primary"></i>
            <span class="fw-semibold">{{ $lead->follow_up_date->format('d M Y') }}</span>
          </div>
          @if($lead->follow_up_time)
          <div class="d-flex align-items-center gap-2">
            <i data-lucide="clock" class="icon-sm text-primary"></i>
            <span>{{ \Carbon\Carbon::parse($lead->follow_up_time)->format('h:i A') }}</span>
          </div>
          @endif
        @else
          <span class="text-muted">Not scheduled</span>
        @endif
      </div>
    </div>

    {{-- Lead Info --}}
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Lead Info</h6>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tr>
            <td class="text-muted ps-3">Lead ID</td>
            <td><span class="badge bg-secondary">{{ $lead->lead_id }}</span></td>
          </tr>
          <tr>
            <td class="text-muted ps-3">Created</td>
            <td>{{ $lead->created_at->format('d M Y') }}</td>
          </tr>
          <tr>
            <td class="text-muted ps-3">Last Activity</td>
            <td>{{ $lead->last_activity_at?->format('d M Y, h:i A') ?? '—' }}</td>
          </tr>
        </table>
      </div>
    </div>

  </div>
</div>
@endsection

@push('custom-scripts')
<script>lucide.createIcons();</script>
@endpush
