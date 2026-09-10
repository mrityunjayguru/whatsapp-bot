@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="#">Faqs</a></li>
    <li class="breadcrumb-item active" aria-current="page">FAQ</li>
  </ol>
  <a href="{{ route('faqs.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm me-2"></i> Add FAQ</a>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between mb-3 align-items-center">
            <h6 class="card-title mb-0">FAQ List</h6>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()"><i data-lucide="refresh-cw" class="icon-sm me-2"></i> Refresh</button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr class="bg-light">
                <th>FAQ ID</th>
                <th>QUESTION</th>
                <th>CATEGORY</th>
                <th>KEYWORDS</th>
                <th>ANSWER PREVIEW</th>
                <th>ATTACHMENT</th>
                <th>URL</th>
                <th>MATCH TYPE</th>
                <th>PRIORITY</th>
                <th>STATUS</th>
                <th>CREATED BY</th>
                <th>CREATED AT</th>
                <th>UPDATED AT</th>
                <th>ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($faqs as $faq)
              <tr>
                <td>{{ $faq->faq_hash_id }}</td>
                <td class="fw-bold">{{ $faq->question }}</td>
                <td><span class="badge bg-light text-dark border">{{ $faq->category ?? 'General' }}</span></td>
                <td>{{ $faq->keywords ?? '--' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($faq->answer, 30) }}</td>
                <td>
                    @if($faq->attachment)
                        <a href="{{ $faq->attachment }}" target="_blank" class="text-primary text-decoration-underline"><i data-lucide="paperclip" class="icon-sm"></i> {{ basename($faq->attachment) }}</a>
                    @else
                        --
                    @endif
                </td>
                <td>
                    @if($faq->url)
                        <a href="{{ $faq->url }}" target="_blank" class="text-primary text-decoration-underline">Link</a>
                    @else
                        --
                    @endif
                </td>
                <td><span class="badge" style="background-color: #e0d4f5; color: #7f32d3;">{{ $faq->match_type ?? 'AI Semantic' }}</span></td>
                <td><span class="badge" style="background-color: #fdf3e7; color: #e4913c;">{{ $faq->priority ?? 'Medium' }}</span></td>
                <td>
                    <span class="badge {{ $faq->is_active ? 'bg-success' : 'bg-secondary' }} text-white" style="opacity: 0.8;">{{ $faq->is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td>{{ $faq->created_by ?? '--' }}</td>
                <td>{{ $faq->created_at->format('Y-m-d') }}</td>
                <td>{{ $faq->updated_at->format('Y-m-d') }}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-link p-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-lucide="more-horizontal" class="icon-sm"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('faqs.edit', $faq->id) }}"><i data-lucide="edit" class="icon-sm me-2 text-warning"></i> Edit FAQ</a></li>
                            <li>
                                <form action="{{ route('faqs.toggle-status', $faq->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="dropdown-item"><i data-lucide="power" class="icon-sm me-2 {{ $faq->is_active ? 'text-danger' : 'text-success' }}"></i> {{ $faq->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('faqs.destroy', $faq->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"><i data-lucide="trash" class="icon-sm me-2 text-danger"></i> Delete</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="14" class="text-center text-muted py-4">No FAQs found. Click "Add FAQ" to create one.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        
      </div>
    </div>
  </div>
</div>
@endsection