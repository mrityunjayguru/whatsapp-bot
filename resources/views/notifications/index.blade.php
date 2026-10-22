@extends('layout.master')

@section('title', 'Notifications & Activities')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item active" aria-current="page">Notifications & Activities</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 pb-2 border-bottom">
          <div>
            <h5 class="card-title mb-1">Notifications & Activity Log</h5>
            <p class="text-secondary fs-13px mb-0">Track all incoming customer interactions, active conversations, and system notifications.</p>
          </div>
        </div>

        {{-- Date-based & Status Filter Form --}}
        <form action="{{ route('notifications.index') }}" method="GET" class="mb-4">
          <div class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
              <label class="form-label fs-12px fw-medium text-secondary mb-1">Date From</label>
              <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3 col-sm-6">
              <label class="form-label fs-12px fw-medium text-secondary mb-1">Date To</label>
              <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 col-sm-6">
              <label class="form-label fs-12px fw-medium text-secondary mb-1">Status</label>
              <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
              </select>
            </div>
            <div class="col-md-2 col-sm-6">
              <div class="form-check mt-3 pt-2">
                <input class="form-check-input" type="checkbox" name="filter_unread" value="1" id="filterUnread" {{ request('filter_unread') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fs-13px" for="filterUnread">
                  Unread only
                </label>
              </div>
            </div>
            <div class="col-md-2 col-sm-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center">
                <i data-lucide="filter" class="icon-sm me-1"></i> Filter
              </button>
              @if(request()->hasAny(['date_from', 'date_to', 'status', 'filter_unread']))
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                  <i data-lucide="rotate-ccw" class="icon-sm"></i>
                </a>
              @endif
            </div>
          </div>
        </form>

        {{-- Notifications & Activity List --}}
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 40px;"></th>
                <th>Activity / Contact</th>
                <th>Channel</th>
                <th>Latest Message</th>
                <th>Assigned To</th>
                <th>Activity Time</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($notifications as $item)
                @php
                  $contactName = $item->contact?->name ?: ($item->contact?->phone_number ?: 'Customer');
                  if (str_starts_with($contactName, 'web:')) {
                    $contactName = 'Widget Visitor (' . substr($item->contact?->phone_number ?? '', 4, 8) . ')';
                  }
                  $isUnread = ($item->unread_count ?? 0) > 0;
                  $channel = strtolower($item->channel ?? 'whatsapp');
                  $channelBadgeClass = $channel === 'whatsapp' ? 'bg-success' : 'bg-info';
                  $timeCarbon = $item->last_message_at ? \Carbon\Carbon::parse($item->last_message_at) : $item->updated_at;
                @endphp
                <tr class="{{ $isUnread ? 'table-light fw-semibold' : '' }}">
                  <td class="text-center">
                    @if($isUnread)
                      <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background-color: #ef4444;" title="Unread activity"></span>
                    @else
                      <span class="d-inline-block rounded-circle bg-secondary opacity-25" style="width: 6px; height: 6px;"></span>
                    @endif
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="w-35px h-35px rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary me-2 fw-bold text-uppercase fs-12px" style="min-width: 35px;">
                        {{ strtoupper(substr($contactName, 0, 1)) }}
                      </div>
                      <div>
                        <div class="d-flex align-items-center">
                          <span class="text-body">{{ $contactName }}</span>
                          @if($item->unread_count > 0)
                            <span class="badge bg-danger ms-2 rounded-pill fs-11px">{{ $item->unread_count }} new</span>
                          @endif
                        </div>
                        <span class="fs-11px text-secondary d-block">{{ $item->contact?->phone_number ?? '' }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge {{ $channelBadgeClass }} text-uppercase fs-11px">
                      {{ $channel }}
                    </span>
                  </td>
                  <td style="max-width: 320px;">
                    <div class="text-truncate text-secondary fs-13px" title="{{ $item->last_message_preview }}">
                      {{ $item->last_message_preview ?: 'Activity logged' }}
                    </div>
                  </td>
                  <td>
                    <span class="fs-12px text-secondary">
                      {{ $item->assignedUser?->name ?? 'Unassigned' }}
                    </span>
                  </td>
                  <td>
                    <div>
                      <span class="fs-13px text-body d-block">{{ $timeCarbon ? $timeCarbon->diffForHumans() : '-' }}</span>
                      <span class="fs-11px text-secondary">{{ $timeCarbon ? $timeCarbon->format('d M Y, h:i A') : '' }}</span>
                    </div>
                  </td>
                  <td class="text-end">
                    <a href="{{ route('conversations.show', $item->id) }}" class="btn btn-xs btn-outline-primary">
                      View Chat
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-5 text-secondary">
                    <div class="py-4">
                      <i data-lucide="bell-off" class="icon-lg text-secondary opacity-50 mb-2"></i>
                      <p class="fs-14px mb-1">No notifications or activities found</p>
                      <p class="fs-12px text-muted mb-0">Try adjusting your date range or filters.</p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
          <div class="mt-4 d-flex justify-content-end">
            {{ $notifications->links() }}
          </div>
        @endif

      </div>
    </div>
  </div>
</div>
@endsection
