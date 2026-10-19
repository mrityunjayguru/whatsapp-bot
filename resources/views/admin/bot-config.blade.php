@extends('layout.master')
@section('content')
<div class="container py-4" style="max-width:900px">
    <h1 class="h4">Bot Settings</h1>
    <p class="text-muted">Configure the bot greeting, human handoff, and fallback messages. Other bot and website widget settings are unchanged.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    @if(!$isCompanyUser)
        <form method="GET" action="{{ route('bot-config.edit') }}" class="mb-3">
            <label class="form-label">Company</label>
            <select class="form-select" name="company_id" onchange="this.form.submit()">
                <option value="">Select company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected($selectedCompany && $selectedCompany->id == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if($config)
        <form method="POST" action="{{ route('bot-config.update') }}">
            @csrf
            @method('PUT')
            @if(!$isCompanyUser)<input type="hidden" name="company_id" value="{{ $selectedCompany->id }}">@endif
            <div class="card mb-3">
                <div class="card-body">
                    @foreach(['greeting' => 'Greeting', 'human_handoff' => 'Human Handoff', 'fallback' => 'Fallback Message'] as $key => $label)
                        <div class="mb-3">
                            <label for="msg_{{ $key }}" class="form-label fw-semibold">{{ $label }}</label>
                            <textarea class="form-control" id="msg_{{ $key }}" rows="3" maxlength="4000"
                                name="messages[{{ $key }}]" required>{{ old('messages.'.$key, $config['templates'][$key] ?? '') }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="text-muted small">Supported variables: <code>{company_name}</code>, <code>{name}</code>, <code>{greeting_prefix}</code>, <code>{support_link}</code>.</p>
            <button type="submit" class="btn btn-primary">Save bot settings</button>
        </form>
    @else
        <div class="alert alert-info">Select a company to edit its bot messages.</div>
    @endif
</div>
@endsection
