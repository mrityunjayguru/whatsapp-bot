@extends('layout.master')

@section('title', 'Conversations')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/select2/select2.min.css') }}" rel="stylesheet" />
  <link href="{{ asset('build/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item active" aria-current="page">Conversations</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="card-title mb-0">Conversations</h6>
            <div>
                <select class="form-select form-select-sm" style="width: auto;">
                    <option>Summary Cards : All</option>
                </select>
            </div>
        </div>
        
        <form action="{{ route('conversations.index') }}" method="GET" id="filterForm">
          <div class="row g-2 mb-4 align-items-center">
            
            <div class="col-md-2">
              <select name="status" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Statuses</option>
                <option value="OPEN" {{ request('status') == 'OPEN' ? 'selected' : '' }}>Open</option>
                <option value="CLOSED" {{ request('status') == 'CLOSED' ? 'selected' : '' }}>Closed</option>
              </select>
            </div>

            <div class="col-md-2">
              <select name="assigned_to" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                <option value="">Assigned To</option>
              </select>
            </div>
            
            <div class="col-md-2">
              <select name="tags[]" class="form-select form-select-sm js-example-basic-multiple" multiple="multiple" data-placeholder="Tags">
                  {{-- Tags options go here --}}
              </select>
            </div>
            
            <div class="col-md-2">
              <div class="input-group input-group-sm flatpickr" id="dateFlatpickr">
                <span class="input-group-text bg-transparent"><i data-lucide="calendar" class="icon-sm text-muted"></i></span>
                <input type="text" name="date" class="form-control" placeholder="Pick a date" value="{{ request('date') }}" data-input>
              </div>
            </div>

            <div class="col-md-1">
              <div class="form-check form-switch mt-2">
                <input type="checkbox" class="form-check-input" id="unreadOnly" name="unread" value="true" {{ request('unread') ? 'checked' : '' }} onchange="document.getElementById('filterForm').submit()">
                <label class="form-check-label" for="unreadOnly">Unread</label>
              </div>
            </div>
            
            <div class="col-md-2">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent"><i data-lucide="search" class="icon-sm text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search Customer" value="{{ request('search') }}">
              </div>
            </div>

            <div class="col-md-1 text-end">
              <button type="submit" class="btn btn-sm btn-primary w-100">Search</button>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th class="pt-0">
                  <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" id="checkAll">
                  </div>
                </th>
                <th class="pt-0">CONVERSATION NO.</th>
                <th class="pt-0">CUSTOMER NAME</th>
                <th class="pt-0">MOBILE</th>
                <th class="pt-0">TAGS</th>
                <th class="pt-0">ASSIGNED TO</th>
                <th class="pt-0">STATUS</th>
                <th class="pt-0">LAST MESSAGE</th>
                <th class="pt-0">LAST ACTIVITY</th>
                <th class="pt-0">UNREAD</th>
                <th class="pt-0 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($conversations as $conversation)
                <tr>
                  <td>
                    <div class="form-check form-check-inline">
                      <input type="checkbox" class="form-check-input row-checkbox" value="{{ $conversation->id }}">
                    </div>
                  </td>
                  <td>#CONV-{{ $conversation->id }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="me-2">
                        @php
                            $contactName = $conversation->contact->custom_name ?? $conversation->contact->whatsapp_profile_name ?? 'Unknown';
                        @endphp
                        <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($contactName) . '&background=random&rounded=true') }}" alt="avatar" class="wd-30 ht-30 rounded-circle">
                      </div>
                      <span>{{ $contactName }}</span>
                    </div>
                  </td>
                  <td>{{ $conversation->contact->phone_number ?? '-' }}</td>
                  <td>
                    @if($conversation->contact && $conversation->contact->tags)
                        @foreach($conversation->contact->tags as $tag)
                          <span class="badge bg-secondary">{{ $tag->tag_name }}</span>
                        @endforeach
                    @else
                        -
                    @endif
                  </td>
                  <td>
                    @if($conversation->assignedUser)
                      <div class="d-flex align-items-center">
                        <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($conversation->assignedUser->name) . '&background=random&rounded=true') }}" alt="avatar" class="wd-30 ht-30 rounded-circle" title="{{ $conversation->assignedUser->name }}">
                      </div>
                    @else
                      -
                    @endif
                  </td>
                  <td>
                    <span class="badge border border-secondary text-secondary rounded-pill">{{ ucfirst(strtolower($conversation->status)) }}</span>
                  </td>
                  <td>{{ $conversation->last_message_preview ?: '-' }}</td>
                  <td>{{ $conversation->last_message_at ? \Carbon\Carbon::parse($conversation->last_message_at)->format('Y-m-d H:i') : '-' }}</td>
                  <td>{{ $conversation->unread_count > 0 ? $conversation->unread_count : '--' }}</td>
                  <td class="text-center">
                    <a href="{{ route('conversations.show', $conversation->id) }}" class="btn btn-sm btn-light btn-icon" title="View">
                      <i data-lucide="eye" class="icon-sm"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center text-muted py-4">No conversations found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3 align-items-center">
          <div class="text-muted small" id="selectedCountText">0 of {{ $conversations->total() }} row(s) selected.</div>
          <div>
            {{ $conversations->links() }}
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('build/plugins/select2/select2.min.js') }}"></script>
  <script src="{{ asset('build/plugins/flatpickr/flatpickr.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    'use strict';

    if ($(".js-example-basic-multiple").length) {
      $(".js-example-basic-multiple").select2();
      
      // Auto submit form on select change
      $(".js-example-basic-multiple").on('change', function() {
        $('#filterForm').submit();
      });
    }

    if($('#dateFlatpickr').length) {
      flatpickr("#dateFlatpickr", {
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
      $('#selectedCountText').text(count + ' of {{ $conversations->total() }} row(s) selected.');
    }
  });
</script>
@endpush
