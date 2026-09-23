<nav class="sidebar">
  <div class="sidebar-header">
    <a href="{{ url('/') }}" class="sidebar-brand">
      <img src="{{ url('build/images/logo.png') }}" class="logo-mini" alt="Chatbot Logo" style="height: 32px; max-width: 130px; object-fit: contain;">
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
        @if(is_null(auth()->user()->company_id))
          {{-- Superadmin Menu --}}
          <li class="nav-item {{ active_class(['companies', 'companies/*']) }}">
            <a href="{{ url('/companies') }}" class="nav-link">
              <i class="link-icon" data-lucide="building"></i>
              <span class="link-title">Companies</span>
            </a>
          </li>
        @else
          {{-- Company Menu --}}
          <li class="nav-item {{ active_class(['contacts', 'contacts/*']) }}">
            <a href="{{ route('contacts.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="book-open"></i>
              <span class="link-title">Contacts</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['tags', 'tags/*']) }}">
            <a href="{{ route('tags.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="tag"></i>
              <span class="link-title">Tags</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['conversations', 'conversations/*']) }}">
            <a href="{{ route('conversations.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="message-square"></i>
              <span class="link-title">Conversations</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['faqs', 'faqs/*']) }}">
            <a href="{{ route('faqs.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="help-circle"></i>
              <span class="link-title">FAQs</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['bot-config', 'bot-config/*']) }}">
            <a href="{{ route('bot-config.edit') }}" class="nav-link">
              <i class="link-icon" data-lucide="settings"></i>
              <span class="link-title">WhatsApp Bot Settings</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['employees', 'employees/*']) }}">
            <a href="{{ url('/employees') }}" class="nav-link">
              <i class="link-icon" data-lucide="users"></i>
              <span class="link-title">Employees</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['widgets', 'widgets/*']) }}">
            <a href="{{ route('widgets.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="users"></i>
              <span class="link-title">Widget</span>
            </a>
          </li>
        @endif
      @endauth
    </ul>
  </div>
</nav>
