@extends('layout.master')

@section('title', 'Leads')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Leads</h4>
  </div>
  <div class="d-flex gap-2">
    @if(auth()->user()->hasPermission('leads.export'))
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-icon-text dropdown-toggle" type="button"
              id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="btn-icon-prepend" data-lucide="download"></i>
        Export <span id="exportSelectedBadge" class="badge bg-primary ms-1" style="display:none;"></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
        <li><h6 class="dropdown-header" id="exportDropdownLabel">Download as</h6></li>
        <li>
          <a class="dropdown-item export-link" id="exportExcel"
             data-base="{{ route('leads.export', array_merge(request()->only('search','status','source','created_by'), ['format'=>'excel'])) }}"
             data-format="excel" href="#">
            <i data-lucide="table-2" class="icon-sm me-2 text-success"></i> Excel (.xlsx)
          </a>
        </li>
        <li>
          <a class="dropdown-item export-link" id="exportCsv"
             data-base="{{ route('leads.export', array_merge(request()->only('search','status','source','created_by'), ['format'=>'csv'])) }}"
             data-format="csv" href="#">
            <i data-lucide="file-text" class="icon-sm me-2 text-primary"></i> CSV (.csv)
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item export-link" id="exportPdf" target="_blank"
             data-base="{{ route('leads.export', array_merge(request()->only('search','status','source','created_by'), ['format'=>'pdf'])) }}"
             data-format="pdf" href="#">
            <i data-lucide="file" class="icon-sm me-2 text-danger"></i> PDF (Print)
          </a>
        </li>
      </ul>
    </div>
    @endif
    @if(auth()->user()->hasPermission('leads.create'))
    <a href="{{ route('leads.create') }}" class="btn btn-primary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="plus"></i>
      Add Lead
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
      <div class="card-body p-0">

        {{-- Search Bar --}}
        <div class="p-3 border-bottom">
          <div class="d-flex flex-wrap gap-2 align-items-center">
            {{-- Search --}}
            <div class="input-group" style="max-width:280px;">
              <span class="input-group-text bg-transparent">
                <i data-lucide="search" class="icon-sm text-muted"></i>
              </span>
              <input type="text" id="leadSearch" class="form-control border-start-0"
                placeholder="Name, phone or company..."
                value="{{ $search ?? '' }}" autocomplete="off">
              @if(!empty($search))
              <a href="{{ route('leads.index', request()->except('search','page')) }}" class="btn btn-outline-secondary" id="clearSearch" title="Clear">
                <i data-lucide="x" class="icon-sm"></i>
              </a>
              @else
              <button type="button" class="btn btn-outline-secondary d-none" id="clearSearch" title="Clear">
                <i data-lucide="x" class="icon-sm"></i>
              </button>
              @endif
            </div>

            {{-- Status Filter --}}
            <select class="form-select form-select-sm filter-select" style="width:150px;" data-param="status">
              <option value="">All Statuses</option>
              @foreach($statuses as $s)
                <option value="{{ $s->id }}" {{ $filterStatus == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
              @endforeach
            </select>

            {{-- Source Filter --}}
            <select class="form-select form-select-sm filter-select" style="width:150px;" data-param="source">
              <option value="">All Sources</option>
              @foreach($sources as $src)
                <option value="{{ $src->id }}" {{ $filterSource == $src->id ? 'selected' : '' }}>{{ $src->name }}</option>
              @endforeach
            </select>

            {{-- Created By Filter --}}
            <select class="form-select form-select-sm filter-select" style="width:150px;" data-param="created_by">
              <option value="">All Users</option>
              @foreach($creators as $c)
                <option value="{{ $c->id }}" {{ $filterCreatedBy == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
              @endforeach
            </select>

            {{-- Follow-up Date Filter --}}
            <input type="date" id="followUpFilter" class="form-control form-control-sm filter-select"
              style="width:160px;" data-param="follow_up_date"
              value="{{ $filterFollowUp ?? '' }}"
              title="Follow-up Date">

            {{-- Clear All Filters --}}
            @if($filterStatus || $filterSource || $filterCreatedBy || $filterFollowUp)
            <a href="{{ route('leads.index', $search ? ['search' => $search] : []) }}"
               class="btn btn-sm btn-outline-danger">
              <i data-lucide="x" class="icon-sm me-1"></i> Clear Filters
            </a>
            @endif
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3" style="width:40px;">
                  <input type="checkbox" id="selectAllLeads" class="form-check-input" title="Select all">
                </th>
                <th class="ps-2">Lead ID</th>
                <th>Lead Name</th>
                <th>Phone</th>
                <th>Interested In</th>
                <th>Source</th>
                <th>Status</th>
                <th>Next Follow-up</th>
                <th>Last Discussion</th>
                <th>Last Updated</th>
                @if(auth()->user()->hasPermission('leads.view') || auth()->user()->hasPermission('leads.edit') || auth()->user()->hasPermission('leads.delete'))
                <th class="pe-4">Action</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse($leads as $lead)
              <tr>
                <td class="ps-3">
                  <input type="checkbox" class="form-check-input lead-checkbox" value="{{ $lead->id }}">
                </td>
                <td class="ps-2 fw-semibold text-primary">{{ $lead->lead_id }}</td>
                <td>
                  <div class="fw-semibold">{{ $lead->lead_name }}</div>
                  @if($lead->company_name)
                    <small class="text-muted">{{ $lead->company_name }}</small>
                  @endif
                </td>
                <td>{{ $lead->phone_number }}</td>
                <td>{{ $lead->interestedIn->title ?? '—' }}</td>
                <td>{{ $lead->leadSource->name ?? '—' }}</td>
                <td>
                  @php
                    $statusName = $lead->leadStatus->name ?? null;
                    $dotColors = [
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
                    <span style="display:inline-block; padding:3px 10px; border-radius:4px; font-size:12px; font-weight:600; color:#fff; background:{{ $bgColor }};">
                      {{ $statusName }}
                    </span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($lead->follow_up_date)
                  @php
                    $fDate = \Carbon\Carbon::parse($lead->follow_up_date)->startOfDay();
                    $today = \Carbon\Carbon::today();
                    if ($fDate->isFuture()) {
                        $dotColor = '#22c55e'; // green
                    } elseif ($fDate->isToday()) {
                        $dotColor = '#f59e0b'; // yellow
                    } else {
                        $dotColor = '#ef4444'; // red overdue
                    }
                  @endphp
                  <span style="width:9px;height:9px;border-radius:50%;background:{{ $dotColor }};display:inline-block;margin-right:5px;vertical-align:middle;"></span>
                    {{ \Carbon\Carbon::parse($lead->follow_up_date)->format('d M Y') }}
                    @if($lead->follow_up_time)
                      <br><small class="text-muted ps-3">{{ \Carbon\Carbon::parse($lead->follow_up_time)->format('h:i A') }}</small>
                    @endif
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td style="max-width:200px;">
                  @php
                    // index 0 = latest (next follow-up), index 1 = previous (last discussion)
                    $lastDiscussion = $lead->recentFollowUps->get(1) ?? $lead->recentFollowUps->get(0);
                  @endphp
                  @if($lastDiscussion?->note)
                    <span class="text-truncate d-block" style="max-width:200px;" title="{{ $lastDiscussion->note }}">
                      {{ $lastDiscussion->note }}
                    </span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @php
                    $updated = $lead->updated_at;
                    $now     = \Carbon\Carbon::now();
                    if ($updated->isToday()) {
                        $lastUpdated = 'Today, ' . $updated->format('g:i A');
                    } elseif ($updated->isYesterday()) {
                        $lastUpdated = 'Yesterday';
                    } else {
                        $lastUpdated = $updated->format('d M Y');
                    }
                  @endphp
                  <small>{{ $lastUpdated }}</small>
                </td>
                @if(auth()->user()->hasPermission('leads.view') || auth()->user()->hasPermission('leads.edit') || auth()->user()->hasPermission('leads.delete'))
                <td class="pe-4">
                  @if(auth()->user()->hasPermission('leads.view'))
                  <a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-outline-primary me-1" title="View">
                    <i data-lucide="eye" class="icon-sm"></i>
                  </a>
                  @endif
                  @if(auth()->user()->hasPermission('leads.edit'))
                  <a href="{{ route('leads.edit', $lead) }}" class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                    <i data-lucide="edit-2" class="icon-sm"></i>
                  </a>
                  @endif
                  <a href="tel:{{ $lead->phone_number }}" class="btn btn-sm btn-outline-success me-1" title="Call {{ $lead->phone_number }}">
                    <i data-lucide="phone" class="icon-sm"></i>
                  </a>
                  <!-- @if(auth()->user()->hasPermission('leads.delete'))
                  <form action="{{ route('leads.destroy', $lead) }}" method="POST" class="d-inline delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                      class="btn btn-sm btn-outline-danger btn-delete"
                      data-lead-id="{{ $lead->lead_id }}">
                      <i data-lucide="trash-2" class="icon-sm"></i>
                    </button>
                  </form>
                  @endif -->
                </td>
                @endif
              </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-5">
                  No leads found.
                  <a href="{{ route('leads.create') }}">Add your first lead</a>.
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($leads->hasPages())
          <div class="d-flex justify-content-end p-3">
            {{ $leads->links() }}
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

  // Live search with debounce
  const searchInput = document.getElementById('leadSearch');
  const clearBtn    = document.getElementById('clearSearch');
  let   searchTimer = null;

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const val = this.value.trim();

      // Show/hide clear button
      if (clearBtn) {
        clearBtn.classList.toggle('d-none', val === '');
      }

      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        const url = new URL(window.location.href);
        if (val) {
          url.searchParams.set('search', val);
        } else {
          url.searchParams.delete('search');
        }
        url.searchParams.delete('page'); // reset to page 1
        window.location.href = url.toString();
      }, 400); // 400ms debounce
    });
  }

  // Clear search button
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      const url = new URL(window.location.href);
      url.searchParams.delete('search');
      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  }

  // Filter selects & date — instant redirect on change
  document.querySelectorAll('.filter-select').forEach(function (el) {
    el.addEventListener('change', function () {
      const url = new URL(window.location.href);
      const param = this.dataset.param;
      if (this.value) {
        url.searchParams.set(param, this.value);
      } else {
        url.searchParams.delete(param);
      }
      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  });

  document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const leadId = this.dataset.leadId;
      const form   = this.closest('form');

      Swal.fire({
        title: 'Delete ' + leadId + '?',
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
  // ── Checkbox select all ──────────────────────────────────────────
  const selectAllCb  = document.getElementById('selectAllLeads');
  const rowCheckboxes = () => document.querySelectorAll('.lead-checkbox');

  if (selectAllCb) {
    selectAllCb.addEventListener('change', function () {
      rowCheckboxes().forEach(function (cb) {
        cb.checked = selectAllCb.checked;
        cb.closest('tr').classList.toggle('table-primary', cb.checked);
      });
      updateExportLinks();
    });

    rowCheckboxes().forEach(function (cb) {
      cb.addEventListener('change', function () {
        this.closest('tr').classList.toggle('table-primary', this.checked);
        // sync header checkbox state
        const all     = rowCheckboxes();
        const checked = document.querySelectorAll('.lead-checkbox:checked');
        selectAllCb.checked       = checked.length === all.length;
        selectAllCb.indeterminate = checked.length > 0 && checked.length < all.length;
        updateExportLinks();
      });
    });
  }

  // ── Export link — append selected IDs ────────────────────────────
  function updateExportLinks() {
    const checked = document.querySelectorAll('.lead-checkbox:checked');
    const badge   = document.getElementById('exportSelectedBadge');
    const label   = document.getElementById('exportDropdownLabel');

    if (checked.length > 0) {
      badge.textContent = checked.length ;
     
      badge.style.display = '';
      if (label) label.textContent = 'Download ' + checked.length + ' leads as';
    } else {
      badge.style.display = 'none';
      if (label) label.textContent = 'Download as';
    }

    document.querySelectorAll('.export-link').forEach(function (link) {
      const base = link.dataset.base;
      const url  = new URL(base, window.location.origin);

      // Remove any previous ids[]
      url.searchParams.delete('ids[]');

      if (checked.length > 0) {
        checked.forEach(function (cb) {
          url.searchParams.append('ids[]', cb.value);
        });
      }

      link.href = url.toString();
    });
  }

  // Init on page load
  updateExportLinks();
</script>
@endpush
