@extends('layout.master')

@section('title', 'Contacts')

@push('plugin-styles')
  <link href="{{ asset('build/plugins/select2/select2.min.css') }}" rel="stylesheet" />
  <link href="{{ asset('build/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item active" aria-current="page">Contacts</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-4">Contacts</h6>
        
        <form action="{{ route('contacts.index') }}" method="GET" id="filterForm">
          <div class="row g-2 mb-4 align-items-center">
            
            <div class="col-md-2">
              <select name="tags[]" class="form-select form-select-sm js-example-basic-multiple" multiple="multiple" data-placeholder="Tags">
                @foreach($allTags as $tag)
                  <option value="{{ $tag->id }}" {{ in_array($tag->id, request('tags', [])) ? 'selected' : '' }}>
                    {{ $tag->tag_name }}
                  </option>
                @endforeach
              </select>
            </div>
            
            <div class="col-md-2">
              <div class="input-group input-group-sm flatpickr" id="createdDateFlatpickr">
                <span class="input-group-text bg-transparent"><i data-lucide="calendar" class="icon-sm text-muted"></i></span>
                <input type="text" name="created_date" class="form-control" placeholder="Created Date" value="{{ request('created_date') }}" data-input>
              </div>
            </div>
            
            <div class="col-md-2">
              <div class="input-group input-group-sm flatpickr" id="lastConversationFlatpickr">
                <span class="input-group-text bg-transparent"><i data-lucide="calendar" class="icon-sm text-muted"></i></span>
                <input type="text" name="last_conversation" class="form-control" placeholder="Last Conversation" value="{{ request('last_conversation') }}" data-input>
              </div>
            </div>

            <div class="col-md-1">
              <div class="form-check form-switch mt-2">
                <input type="checkbox" class="form-check-input" id="hasEmail" name="has_email" value="true" {{ request('has_email') ? 'checked' : '' }} onchange="document.getElementById('filterForm').submit()">
                <label class="form-check-label" for="hasEmail">Has Email</label>
              </div>
            </div>

            <div class="col-md-1">
              <div class="form-check form-switch mt-2">
                <input type="checkbox" class="form-check-input" id="hasTags" name="has_tags" value="true" {{ request('has_tags') ? 'checked' : '' }} onchange="document.getElementById('filterForm').submit()">
                <label class="form-check-label" for="hasTags">Has Tags</label>
              </div>
            </div>
            
            <div class="col-md-3">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent"><i data-lucide="search" class="icon-sm text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search Contact" value="{{ request('search') }}">
              </div>
            </div>

            <div class="col-md-1 text-end">
              <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
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
                <th class="pt-0">CONTACT ID</th>
                <th class="pt-0">CUSTOMER NAME</th>
                <th class="pt-0">WHATSAPP NAME</th>
                <th class="pt-0">MOBILE</th>
                <th class="pt-0">EMAIL</th>
                <th class="pt-0">TAGS</th>
                <th class="pt-0">TOTAL CONVERSATIONS</th>
                <th class="pt-0">WHATSAPP PHONE NUMBER ID</th>
                <th class="pt-0">LAST CONVERSATION</th>
                <th class="pt-0">CREATED AT</th>
                <th class="pt-0 text-center">ACTION VIEW</th>
              </tr>
            </thead>
            <tbody>
              @forelse($contacts as $contact)
                <tr>
                  <td>
                    <div class="form-check form-check-inline">
                      <input type="checkbox" class="form-check-input row-checkbox" value="{{ $contact->id }}">
                    </div>
                  </td>
                  <td>#{{ $contact->id }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="me-2">
                        @if($contact->custom_name || $contact->whatsapp_profile_name)
                          <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($contact->custom_name ?? $contact->whatsapp_profile_name) . '&background=random&rounded=true') }}" alt="avatar" class="wd-30 ht-30 rounded-circle">
                        @else
                          <img src="{{ url('https://ui-avatars.com/api/?name=User&background=random&rounded=true') }}" alt="avatar" class="wd-30 ht-30 rounded-circle">
                        @endif
                      </div>
                      <span>{{ $contact->custom_name ?? $contact->whatsapp_profile_name ?? 'Unknown' }}</span>
                    </div>
                  </td>
                  <td>{{ $contact->whatsapp_profile_name ?? '-' }}</td>
                  <td>{{ $contact->phone_number }}</td>
                  <td>{{ $contact->email ?? '-' }}</td>
                  <td>
                    @foreach($contact->tags as $tag)
                      <span class="badge bg-secondary">{{ $tag->tag_name }}</span>
                    @endforeach
                  </td>
                  <td>{{ $contact->conversations_count }} Conversations</td>
                  <td>{{ $contact->whatsapp_phone_number_id ?? '-' }}</td>
                  <td>{{ $contact->conversations->first() ? $contact->conversations->first()->created_at->format('Y-m-d H:i:s') : '-' }}</td>
                  <td>{{ $contact->created_at->format('Y-m-d\TH:i:s.u') }}</td>
                  <td class="text-center">
                    <a href="{{ route('contacts.show', $contact->id) }}" class="btn btn-sm btn-light btn-icon" title="View">
                      <i data-lucide="eye" class="icon-sm"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="12" class="text-center text-muted py-4">No contacts found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3 align-items-center">
          <div class="text-muted small" id="selectedCountText">0 of {{ $contacts->total() }} row(s) selected.</div>
          <div>
            {{ $contacts->links() }}
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

    if($('#createdDateFlatpickr').length) {
      flatpickr("#createdDateFlatpickr", {
        wrap: true,
        dateFormat: "Y-m-d",
        onChange: function() {
           $('#filterForm').submit();
        }
      });
    }

    if($('#lastConversationFlatpickr').length) {
      flatpickr("#lastConversationFlatpickr", {
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
      $('#selectedCountText').text(count + ' of {{ $contacts->total() }} row(s) selected.');
    }
  });
</script>
@endpush
