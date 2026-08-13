<nav class="sidebar">
  <div class="sidebar-header">
    <a href="{{ url('/') }}" class="sidebar-brand">
      <img src="{{ url('build/images/loopie-dark.svg') }}" class="logo-mini-light" alt="Loopie" style="height: 32px; max-width: 130px; object-fit: contain;">
      <img src="{{ url('build/images/loopie-light.svg') }}" class="logo-mini-dark" alt="Loopie" style="height: 32px; max-width: 130px; object-fit: contain;">
    </a>
    <div class="sidebar-toggler not-active">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
  <div class="sidebar-body">
    <ul class="nav" id="sidebarNav">

      <li class="nav-item {{ active_class(['/']) }}">
        <a href="{{ url('/') }}" class="nav-link">
          <i class="link-icon" data-lucide="home"></i>
          <span class="link-title">Dashboard</span>
        </a>
      </li>

      @auth
      @php
        $user        = auth()->user();
        $isSuper     = $user->role_id === 1;
        $isCompany   = $user->role_id === 2;
        $isSubUser   = !$isSuper && !$isCompany && $user->created_by !== null;
      @endphp

      {{-- Superadmin only --}}
      @if($isSuper)
      <li class="nav-item {{ active_class(['company', 'company/*']) }}">
        <a href="{{ route('company.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="building"></i>
          <span class="link-title">Company</span>
        </a>
      </li>
      @endif

      {{-- Company owner only --}}
      @if($isCompany)
      <li class="nav-item {{ active_class(['roles', 'roles/*']) }}">
        <a href="{{ route('roles.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="shield"></i>
          <span class="link-title">Roles & Permissions</span>
        </a>
      </li>
      <li class="nav-item {{ active_class(['users', 'users/*']) }}">
        <a href="{{ route('users.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="users"></i>
          <span class="link-title">Users</span>
        </a>
      </li>
      @endif

      {{-- Company owner — all modules --}}
      @if($isCompany)
      <li class="nav-item {{ active_class(['lead-source', 'lead-source/*']) }}">
        <a href="{{ route('lead-source.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="radio"></i>
          <span class="link-title">Lead Source</span>
        </a>
      </li>
      <li class="nav-item {{ active_class(['lead-status', 'lead-status/*']) }}">
        <a href="{{ route('lead-status.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="tag"></i>
          <span class="link-title">Lead Status</span>
        </a>
      </li>
      <li class="nav-item {{ active_class(['interested-in', 'interested-in/*']) }}">
        <a href="{{ route('interested-in.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="heart"></i>
          <span class="link-title">Interested In</span>
        </a>
      </li>
      <li class="nav-item {{ active_class(['leads', 'leads/*']) }}">
        <a href="{{ route('leads.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="user-plus"></i>
          <span class="link-title">Leads</span>
        </a>
      </li>
      @endif

      {{-- Sub-user — show menu items based on permissions --}}
      @if($isSubUser)
      @if($user->hasPermission('lead-source.view'))
      <li class="nav-item {{ active_class(['lead-source', 'lead-source/*']) }}">
        <a href="{{ route('lead-source.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="radio"></i>
          <span class="link-title">Lead Source</span>
        </a>
      </li>
      @endif
      @if($user->hasPermission('lead-status.view'))
      <li class="nav-item {{ active_class(['lead-status', 'lead-status/*']) }}">
        <a href="{{ route('lead-status.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="tag"></i>
          <span class="link-title">Lead Status</span>
        </a>
      </li>
      @endif
      @if($user->hasPermission('interested-in.view'))
      <li class="nav-item {{ active_class(['interested-in', 'interested-in/*']) }}">
        <a href="{{ route('interested-in.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="heart"></i>
          <span class="link-title">Interested In</span>
        </a>
      </li>
      @endif
      @if($user->hasPermission('leads.view'))
      <li class="nav-item {{ active_class(['leads', 'leads/*']) }}">
        <a href="{{ route('leads.index') }}" class="nav-link">
          <i class="link-icon" data-lucide="user-plus"></i>
          <span class="link-title">Leads</span>
        </a>
      </li>
      @endif
      @endif

      @endauth

    </ul>
  </div>
</nav>
