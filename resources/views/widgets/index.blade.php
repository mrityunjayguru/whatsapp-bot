@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex align-items-center justify-content-between mb-4">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item active" aria-current="page">Website Widgets</li>
  </ol>
  @if(is_null(auth()->user()->company_id))
  <a href="{{ route('widgets.create') }}" class="btn btn-primary">
    <i data-lucide="plus" class="icon-sm me-1"></i> Add Widget
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
    <h6 class="card-title text-muted mb-4 border-bottom pb-2">WIDGET LIST</h6>

    @if (empty($widgets))
      @if(is_null(auth()->user()->company_id))
        <p class="text-muted mb-0">No widgets yet - click "Add Widget" to create one for a site.</p>
      @else
        <p class="text-muted mb-0">No widgets found for your company. Please contact support or your administrator.</p>
      @endif
    @else
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Company</th>
              <th>Site</th>
              <th>Contact</th>
              <th>Bot Name</th>
              <th>Status</th>
              <th>Token</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($widgets as $widget)
              <tr>
                <td class="fw-bold">{{ $widget['company_name'] }}</td>
                <td>{{ $widget['site_name'] }}</td>
                <td class="text-muted small">{{ $widget['contact_email'] ?: '--' }}</td>
                <td>{{ $widget['bot_name'] }}</td>
                <td>
                  @if ($widget['is_active'])
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
                <td><code class="small">{{ $widget['token'] }}</code></td>
                <td class="text-end">
                  @if(is_null(auth()->user()->company_id))
                    <a href="{{ route('widgets.edit', $widget['token']) }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                    <form action="{{ route('widgets.destroy', $widget['token']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Permanently delete this widget, its config, and ALL of its FAQs/files? This cannot be undone.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  @else
                    <a href="{{ route('widgets.edit', $widget['token']) }}" class="btn btn-sm btn-outline-secondary">View</a>
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
