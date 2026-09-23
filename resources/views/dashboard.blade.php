@extends('layout.master')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Chatbot Dashboard</h4>
  </div>
</div>

<div class="row">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-2">Total Contacts</h6>
        <h3 class="mb-2">{{ $stats['total_contacts'] ?? 0 }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-2">Total Conversations</h6>
        <h3 class="mb-2">{{ $stats['total_conversations'] ?? 0 }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-2">Total Messages</h6>
        <h3 class="mb-2">{{ $stats['total_messages'] ?? 0 }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-2">Active Bots</h6>
        <h3 class="mb-2">{{ $stats['active_bots'] ?? 0 }}</h3>
      </div>
    </div>
  </div>
</div>
@endsection
