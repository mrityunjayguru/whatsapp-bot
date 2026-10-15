@extends('layout.master2')

@section('content')
<div class="row w-100 mx-0 auth-page">
  <div class="col-md-8 col-xl-6 mx-auto">
    <div class="card shadow-sm border-0">
      <div class="row">
        <div class="col-md-4 pe-md-0">
          <div class="auth-side-wrapper" style="background-image: url({{ url('https://placehold.co/220x450') }})"></div>
        </div>
        <div class="col-md-8 ps-md-0">
          <div class="auth-form-wrapper px-4 py-5">
            <a href="#" class="d-block mb-3">
              <img src="{{ url('build/images/logo.png') }}" class="logo-mini" alt="Design Demonz" style="height: 36px; max-width: 150px; object-fit: contain;">
            </a>
            
            <div class="d-flex align-items-center mb-3">
              <div class="badge bg-primary-subtle text-primary p-2 rounded-circle me-2">
                <i data-lucide="mail-check" style="width: 20px; height: 20px;"></i>
              </div>
              <h4 class="mb-0 fw-bold">Verify Your Email</h4>
            </div>

            <p class="text-secondary mb-4" style="line-height: 1.6; font-size: 0.95rem;">
              {{ __('Thanks for signing up! Before getting started, please check your inbox and verify your email address by clicking on the link we just sent. If you didn\'t receive the email, we will happily send you another.') }}
            </p>

            @if (session('status') == 'verification-link-sent')
              <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                <i data-lucide="check-circle" class="me-2" style="width: 18px; height: 18px;"></i>
                <div>
                  {{ __('A new verification link has been sent to the email address you provided.') }}
                </div>
              </div>
            @endif

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4 pt-2 border-top">
              <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                  <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                  <span>{{ __('Resend Verification Email') }}</span>
                </button>
              </form>

              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                  <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                  <span>{{ __('Log Out') }}</span>
                </button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
