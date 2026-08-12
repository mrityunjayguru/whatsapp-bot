@extends('layout.master')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Dashboard</h4>
  </div>
  <div>
    <small class="text-muted">{{ \Carbon\Carbon::now()->format('l, d M Y') }}</small>
  </div>
</div>

@if($stats)
{{-- Stats Cards --}}
<div class="row g-4 mb-4">

  {{-- Today's Leads --}}
  <div class="col-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3 py-4">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10"
          style="width:52px;height:52px;">
          <i data-lucide="user-plus" class="text-primary" style="width:24px;height:24px;"></i>
        </div>
        <div>
          <div class="fs-28px fw-bold lh-1 mb-1">{{ $stats['today_leads'] }}</div>
          <div class="text-muted small">Today's Leads</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Today's Follow-ups --}}
  <div class="col-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3 py-4">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-info bg-opacity-10"
          style="width:52px;height:52px;">
          <i data-lucide="calendar-check" class="text-info" style="width:24px;height:24px;"></i>
        </div>
        <div>
          <div class="fs-28px fw-bold lh-1 mb-1">{{ $stats['today_followups'] }}</div>
          <div class="text-muted small">Today's Follow-ups</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Pending Follow-ups --}}
  <div class="col-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3 py-4">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-warning bg-opacity-10"
          style="width:52px;height:52px;">
          <i data-lucide="clock" class="text-warning" style="width:24px;height:24px;"></i>
        </div>
        <div>
          <div class="fs-28px fw-bold lh-1 mb-1">{{ $stats['pending_followups'] }}</div>
          <div class="text-muted small">Pending Follow-ups</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Interested Leads --}}
  <div class="col-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3 py-4">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10"
          style="width:52px;height:52px;">
          <i data-lucide="thumbs-up" class="text-success" style="width:24px;height:24px;"></i>
        </div>
        <div>
          <div class="fs-28px fw-bold lh-1 mb-1">{{ $stats['interested_leads'] }}</div>
          <div class="text-muted small">Interested Leads</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Won This Month --}}
  <div class="col-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3 py-4">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10"
          style="width:52px;height:52px;">
          <i data-lucide="trophy" class="text-success" style="width:24px;height:24px;"></i>
        </div>
        <div>
          <div class="fs-28px fw-bold lh-1 mb-1">{{ $stats['won_this_month'] }}</div>
          <div class="text-muted small">Won This Month</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Lost This Month --}}
  <div class="col-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3 py-4">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-danger bg-opacity-10"
          style="width:52px;height:52px;">
          <i data-lucide="x-circle" class="text-danger" style="width:24px;height:24px;"></i>
        </div>
        <div>
          <div class="fs-28px fw-bold lh-1 mb-1">{{ $stats['lost_this_month'] }}</div>
          <div class="text-muted small">Lost This Month</div>
        </div>
      </div>
    </div>
  </div>

</div>
{{-- Upcoming Follow-ups --}}
<div class="row mb-4">
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <h6 class="mb-0">
          <i data-lucide="calendar-clock" class="icon-sm me-2 text-primary"></i>
          Follow-ups
        </h6>
        <div class="d-flex align-items-center gap-2">
          <input type="date" id="followUpDateFilter"
            class="form-control form-control-sm"
            value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
            style="width:150px;">
          <span class="badge bg-primary" id="followUpCount">{{ $upcomingFollowUps->count() }}</span>
        </div>
      </div>
      <div class="card-body p-0" id="followUpList">
        @if($upcomingFollowUps->count())
        <ul class="list-group list-group-flush">
          @foreach($upcomingFollowUps as $lead)
          <li class="list-group-item px-4 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="text-center flex-shrink-0" style="min-width:62px;">
                @if($lead->follow_up_time)
                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1" style="font-size:12px;">
                  {{ \Carbon\Carbon::parse($lead->follow_up_time)->format('h:i A') }}
                </span>
                @else
                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold px-2 py-1" style="font-size:12px;">
                  No time
                </span>
                @endif
              </div>
              <div class="vr opacity-25"></div>
              <div class="flex-grow-1">
                <div class="fw-semibold">{{ $lead->lead_name }}</div>
                <div class="text-muted small text-truncate" style="max-width:220px;">{{ $lead->requirement }}</div>
              </div>
              @if(auth()->user()->hasPermission('leads.edit'))
              <a href="{{ route('leads.edit', $lead) }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                <i data-lucide="edit-2" class="icon-sm"></i>
              </a>
              @endif
            </div>
          </li>
          @endforeach
        </ul>
        @else
        <div class="text-center text-muted py-4">
          <i data-lucide="calendar-x" class="mb-2" style="width:32px;height:32px;opacity:0.4;"></i>
          <p class="mb-0">No follow-ups for today.</p>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Recent Activities --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">
          <i data-lucide="activity" class="icon-sm me-2 text-primary"></i>
          Recent Activities
        </h6>
        {{-- Filter dropdown --}}
        <select class="form-select form-select-sm w-auto" id="activityFilterSelect">
          <option value="all">All Activities</option>
          <option value="lead_created">Lead Created</option>
          <option value="follow_up_added">Follow-up Added</option>
          <option value="status_changed">Status Changed</option>
          <option value="lead_closed">Lead Closed</option>
        </select>
      </div>
      <div class="card-body p-0">
        @if($activities->count())
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="activitiesTable">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Type</th>
                <th>Lead</th>
                <th>Description</th>
                <th>Performed By</th>
                <th class="pe-4">Date & Time</th>
              </tr>
            </thead>
            <tbody>
              @foreach($activities as $activity)
              <tr data-type="{{ $activity->type }}">
                <td class="ps-4">
                  <span class="badge {{ \App\Models\LeadActivity::typeBadge($activity->type) }}">
                    {{ \App\Models\LeadActivity::typeLabel($activity->type) }}
                  </span>
                </td>
                <td>
                  @if($activity->lead)
                    @if(auth()->user()->hasPermission('leads.edit'))
                    <a href="{{ route('leads.edit', $activity->lead) }}" class="fw-semibold text-primary text-decoration-none">
                      {{ $activity->lead->lead_id }}
                    </a>
                    @else
                    <span class="fw-semibold">{{ $activity->lead->lead_id }}</span>
                    @endif
                    <div class="text-muted small">{{ $activity->lead->lead_name }}</div>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-muted" style="max-width:320px;">{{ $activity->description }}</td>
                <td>{{ $activity->performer->name ?? '—' }}</td>
                <td class="pe-4">
                  <div>{{ $activity->created_at->format('d M Y') }}</div>
                  <small class="text-muted">{{ $activity->created_at->format('h:i A') }}</small>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center text-muted py-5">
          <i data-lucide="inbox" style="width:36px;height:36px;opacity:0.3;" class="mb-2"></i>
          <p class="mb-0">No activities yet.</p>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

@else
{{-- Superadmin view --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body text-center py-5">
        <i data-lucide="layout-dashboard" style="width:48px;height:48px;" class="text-muted mb-3"></i>
        <h5 class="text-muted">Welcome, Super Admin</h5>
        <p class="text-muted mb-0">Manage companies from the sidebar.</p>
      </div>
    </div>
  </div>
</div>
@endif

@endsection

@push('custom-scripts')
<script>
  lucide.createIcons();

  // Activity filter dropdown
  const activitySelect = document.getElementById('activityFilterSelect');
  if (activitySelect) {
    activitySelect.addEventListener('change', function () {
      const type = this.value;
      document.querySelectorAll('#activitiesTable tbody tr').forEach(function (row) {
        row.style.display = (type === 'all' || row.dataset.type === type) ? '' : 'none';
      });
    });
  }

  const dateFilter  = document.getElementById('followUpDateFilter');
  const listEl      = document.getElementById('followUpList');
  const countEl     = document.getElementById('followUpCount');
  const apiUrl      = '{{ route("dashboard.follow-ups") }}';
  const csrfToken   = document.querySelector('meta[name="_token"]').getAttribute('content');

  if (dateFilter) {
    dateFilter.addEventListener('change', function () {
      fetchFollowUps(this.value);
    });
  }

  function fetchFollowUps(date) {
    if (!listEl) return;

    listEl.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Loading...</div>';

    fetch(apiUrl + '?date=' + date, {
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
      if (countEl) countEl.textContent = data.length;

      if (data.length === 0) {
        if (countEl) countEl.textContent = 0;
        listEl.innerHTML = `<div class="text-center text-muted py-4">
          <i data-lucide="calendar-x" class="mb-2" style="width:32px;height:32px;opacity:0.4;"></i>
          <p class="mb-0">No follow-ups for this date.</p>
        </div>`;
        lucide.createIcons();
        return;
      }

      let html = '<ul class="list-group list-group-flush">';
      data.forEach(function (lead) {
        html += `
          <li class="list-group-item px-4 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="text-center flex-shrink-0" style="min-width:62px;">
                ${lead.time
                  ? `<span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1" style="font-size:12px;">${lead.time}</span>`
                  : `<span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold px-2 py-1" style="font-size:12px;">No time</span>`
                }
              </div>
              <div class="vr opacity-25"></div>
              <div class="flex-grow-1">
                <div class="fw-semibold">${lead.lead_name}</div>
                <div class="text-muted small" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${lead.requirement}</div>
              </div>
              ${lead.edit_url
                ? `<a href="${lead.edit_url}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                     <i data-lucide="edit-2" class="icon-sm"></i>
                   </a>`
                : ''
              }
            </div>
          </li>`;
      });
      html += '</ul>';
      listEl.innerHTML = html;
      lucide.createIcons();
    })
    .catch(() => {
      listEl.innerHTML = '<div class="text-center text-danger py-4">Failed to load. Please try again.</div>';
    });
  }
</script>
@endpush
