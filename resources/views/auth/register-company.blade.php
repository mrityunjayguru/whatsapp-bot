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
                <label for="company_name" class="form-label">Company Name</label>
                <input type="text" class="form-control" name="company_name" id="company_name" autocomplete="organization" placeholder="Company Name" value="{{ old('company_name') }}" required autofocus>
                <x-input-error :messages="$errors->get('company_name')" class="mt-2 text-danger" />
              </div>

              <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="text" class="form-control" name="contact_number" id="contact_number" autocomplete="tel" placeholder="Contact Number" value="{{ old('contact_number') }}" required>
                <x-input-error :messages="$errors->get('contact_number')" class="mt-2 text-danger" />
              </div>


              <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="Email" value="{{ old('email') }}" required autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger" />
              </div>
              
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="password" autocomplete="new-password" placeholder="Password" required>
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger" />
              </div>
              
              <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" autocomplete="new-password" placeholder="Confirm Password" required>
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
