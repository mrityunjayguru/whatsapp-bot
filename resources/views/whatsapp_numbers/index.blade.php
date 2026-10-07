@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex align-items-center justify-content-between mb-4">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item active" aria-current="page">WhatsApp Numbers</li>
  </ol>
  @if(is_null(auth()->user()->company_id))
  <a href="{{ route('whatsapp-numbers.create') }}" class="btn btn-primary">
    <i data-lucide="plus" class="icon-sm me-1"></i> Add WhatsApp Number
  </a>
  @endif
</nav>

@if (session('success'))
  <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger mb-4">{{ session('error') }}</div>
@endif

<div class="card">
  <div class="card-body">
    <h6 class="card-title text-muted mb-4 border-bottom pb-2">WHATSAPP NUMBER LIST</h6>

    @if ($numbers->isEmpty())
      @if(is_null(auth()->user()->company_id))
        <p class="text-muted mb-0">No WhatsApp numbers yet - click "Add WhatsApp Number" to register a client's.</p>
      @else
        <p class="text-muted mb-0">No WhatsApp numbers found for your company. Please contact support or your administrator.</p>
      @endif
    @else
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Company</th>
              <th>Label</th>
              <th>Display Number</th>
              <th>Status</th>
              <th>phone_number_id</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($numbers as $number)
              <tr>
                <td class="fw-bold">{{ $number->company->name ?? '--' }}</td>
                <td>{{ $number->label ?: '--' }}</td>
                <td>{{ $number->display_number ?: '--' }}</td>
                <td>
                  @if ($number->isCurrentlyActive())
                    <span class="badge bg-success">Active</span>
                  @elseif ($number->is_active)
                    <span class="badge bg-warning text-dark">Outside date window</span>
                  @else
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
                <td><code class="small">{{ $number->phone_number_id }}</code></td>
                <td class="text-end">
                  @if(is_null(auth()->user()->company_id))
                    <a href="{{ route('whatsapp-numbers.edit', $number->phone_number_id) }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                    <form action="{{ route('whatsapp-numbers.destroy', $number->phone_number_id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Permanently remove this WhatsApp number, its bot config, and ALL of its FAQs? This cannot be undone.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  @else
                    <a href="{{ route('whatsapp-numbers.edit', $number->phone_number_id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
