@extends('layout.master2')

@section('content')
<div class="row w-100 mx-0 auth-page">
  <div class="col-md-8 col-xl-6 mx-auto">
    <div class="card">
      <div class="row">
        <div class="col-md-4 pe-md-0">
          <div class="auth-side-wrapper" style="background-image: url({{ url('https://placehold.co/220x450') }})">
          </div>
        </div>
        <div class="col-md-8 ps-md-0">
          <div class="auth-form-wrapper px-4 py-5">
            <a href="#" class="d-block mb-2">
              <img src="{{ url('build/images/logo.png') }}" class="logo-mini" alt="Design Demonz" style="height: 36px; max-width: 150px; object-fit: contain;">
            </a>
            <h5 class="text-secondary fw-normal mb-4">Register your company.</h5>
            
            <form method="POST" action="{{ route('register.company') }}" class="forms-sample">
              @csrf

              @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
              @endif

              <div class="mb-3">
                <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="company_name" id="company_name" autocomplete="organization" placeholder="Company Name" value="{{ old('company_name') }}" required autofocus>
                <x-input-error :messages="$errors->get('company_name')" class="mt-2 text-danger" />
              </div>

              <div class="mb-3">
                <label for="company_code" class="form-label">Company ID / Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="company_code" id="company_code" placeholder="e.g. MYCOMP or DES1234" value="{{ old('company_code') }}" required style="text-transform: uppercase;">
                <small class="text-secondary d-block mt-1">Unique company code. Employees will use this Company ID to log in.</small>
                <x-input-error :messages="$errors->get('company_code')" class="mt-2 text-danger" />
              </div>

              <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="text" class="form-control" name="contact_number" id="contact_number" autocomplete="tel" placeholder="Contact Number" value="{{ old('contact_number') }}">
                <x-input-error :messages="$errors->get('contact_number')" class="mt-2 text-danger" />
              </div>

              <div class="mb-3">
                <label class="form-label d-block">Preferred Channel <span class="text-danger">*</span></label>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="card h-100 p-2 border cursor-pointer channel-card" for="channel_widget" style="cursor: pointer;">
                      <div class="d-flex align-items-center">
                        <input type="radio" class="form-check-input me-2 mt-0" name="bot_usage_type" id="channel_widget" value="widget" {{ old('bot_usage_type', 'widget') === 'widget' ? 'checked' : '' }} required>
                        <div>
                          <div class="fw-semibold fs-13px d-flex align-items-center">
                            Website Widget
                          </div>
                          <small class="text-secondary fs-11px">Web live chat widget</small>
                        </div>
                      </div>           
                    </label>
                  </div>
                  <div class="col-6">
                    <label class="card h-100 p-2 border cursor-pointer channel-card" for="channel_whatsapp" style="cursor: pointer;">
                      <div class="d-flex align-items-center">
                        <input type="radio" class="form-check-input me-2 mt-0" name="bot_usage_type" id="channel_whatsapp" value="whatsapp" {{ old('bot_usage_type') === 'whatsapp' ? 'checked' : '' }} required>
                        <div>
                          <div class="fw-semibold fs-13px d-flex align-items-center">
                           WhatsApp
                          </div>
                          <small class="text-secondary fs-11px">WhatsApp Cloud API</small>
                        </div>
                      </div>
                    </label>
                  </div>
                </div>
                <x-input-error :messages="$errors->get('bot_usage_type')" class="mt-2 text-danger" />
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" id="email" placeholder="Email" value="{{ old('email') }}" required autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger" />
              </div>
              
              <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" class="form-control" name="password" id="password" autocomplete="new-password" placeholder="Password" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                    <i data-lucide="eye" id="eyeIconPassword" style="width:16px;height:16px;"></i>
                  </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger" />
              </div>
              
              <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" autocomplete="new-password" placeholder="Confirm Password" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm" tabindex="-1">
                    <i data-lucide="eye" id="eyeIconConfirm" style="width:16px;height:16px;"></i>
                  </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-danger" />
              </div>
              
              <div>
                <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0">Register Company</button>
              </div>
              <p class="mt-3 text-secondary">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
    function setupToggle(btnId, inputId, iconId) {
        var btn = document.getElementById(btnId);
        var input = document.getElementById(inputId);
        var icon = document.getElementById(iconId);
        if (!btn || !input || !icon) return;
        btn.addEventListener('click', function() {
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        setupToggle('togglePassword', 'password', 'eyeIconPassword');
        setupToggle('togglePasswordConfirm', 'password_confirmation', 'eyeIconConfirm');
    });
</script>
@endpush
