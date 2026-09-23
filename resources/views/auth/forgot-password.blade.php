@extends('layout.master2')

@section('content')
<div class="row w-100 mx-0 auth-page">
  <div class="col-md-8 col-xl-6 mx-auto">
    <div class="card">
      <div class="row">
        <div class="col-md-4 pe-md-0">
          <div class="auth-side-wrapper" style="background-image: url({{ url('build/images/photos/img6.jpg') }})">

          </div>
        </div>
        <div class="col-md-8 ps-md-0">
          <div class="auth-form-wrapper px-4 py-5">
            <a href="#" class="d-block mb-2">
              <img src="{{ url('build/images/logo.png') }}" class="logo-mini" alt="Design Demonz" style="height: 36px; max-width: 150px; object-fit: contain;">
            </a>
            <h4 class="mb-4">Forgot your password?</h4>
            <p class="mb-4 text-secondary">
              Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
            </p>
            
            <x-auth-session-status class="mb-4 text-success" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="forms-sample">
              @csrf

              <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="Email" value="{{ old('email') }}" required autofocus>
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger" />
              </div>
              <div>
                <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0 text-white">Email Password Reset Link</button>
                <a href="{{ route('login') }}" class="btn btn-link">Back to Login</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
