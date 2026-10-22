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

      <!-- <li class="nav-item {{ active_class(['/']) }}">
        <a href="{{ url('/') }}" class="nav-link">
          <i class="link-icon" data-lucide="home"></i>
          <span class="link-title">Dashboard</span>
        </a>
      </li> -->

      @auth
        @if(is_null(auth()->user()->company_id))
          {{-- Superadmin Menu --}}
          <li class="nav-item {{ active_class(['companies', 'companies/*']) }}">
            <a href="{{ url('/companies') }}" class="nav-link">
              <i class="link-icon" data-lucide="building"></i>
              <span class="link-title">Companies</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['widget', 'widget/*']) }}">
            <a href="{{ route('widgets.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="layout-template"></i>
              <span class="link-title">Widgets</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['whatsapp-numbers', 'whatsapp-numbers/*']) }}">
            <a href="{{ route('whatsapp-numbers.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="phone"></i>
              <span class="link-title">WhatsApp Numbers</span>
            </a>
          </li>
        @else
          {{-- Company Menu --}}
          @php
            $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
            $isRegularEmployee = $employee && $employee->role !== 'ADMIN';

            $sidebarUnreadQuery = \App\Models\Conversation::where('tenant_id', auth()->user()->company_id ?? auth()->user()->tenant_id)
                ->where('unread_count', '>', 0);
            if ($isRegularEmployee) {
                $sidebarUnreadQuery->where(function($q) use ($employee) {
                    $q->where('assigned_tenant_user_id', $employee->id)
                      ->orWhereJsonContains('assignment_history', ['employee_id' => $employee->id])
                      ->orWhereJsonContains('assignment_history', ['employee_id' => (string)$employee->id]);
                });
            }
            $unreadCount = (int) $sidebarUnreadQuery->sum('unread_count');
          @endphp

          @if(auth()->user()->canAccess('contacts.view'))
          <li class="nav-item {{ active_class(['contacts', 'contacts/*']) }}">
            <a href="{{ route('contacts.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="book-open"></i>
              <span class="link-title">Contacts</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('tags.view'))
          <li class="nav-item {{ active_class(['tags', 'tags/*']) }}">
            <a href="{{ route('tags.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="tag"></i>
              <span class="link-title">Tags</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('conversations.view'))
          <li class="nav-item {{ active_class(['conversations', 'conversations/*']) }}">
            <a href="{{ route('conversations.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="message-square"></i>
              <span class="link-title d-inline-flex align-items-center">
                Conversations
                <span class="nav-unread-dot ms-1" id="sidebarConversationsUnreadDot" style="display: {{ ($unreadCount ?? 0) > 0 ? 'inline-block' : 'none' }}; width: 7px; height: 7px; background-color: #ef4444; border-radius: 50%; transform: translateY(-4px); box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);"></span>
              </span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('faqs.view'))
          <li class="nav-item {{ active_class(['faqs', 'faqs/*']) }}">
            <a href="{{ route('faqs.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="help-circle"></i>
              <span class="link-title">FAQs</span>
            </a>
          </li>
          <li class="nav-item {{ active_class(['faq-categories', 'faq-categories/*']) }}">
            <a href="{{ route('faq-categories.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="folder"></i>
              <span class="link-title">FAQ Categories</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('employees.view'))
          <li class="nav-item {{ active_class(['employees', 'employees/*']) }}">
            <a href="{{ url('/employees') }}" class="nav-link">
              <i class="link-icon" data-lucide="users"></i>
              <span class="link-title">Employees</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('departments.manage'))
          <li class="nav-item {{ active_class(['departments', 'departments/*']) }}">
            <a href="{{ route('departments.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="building"></i>
              <span class="link-title">Departments</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('designations.manage'))
          <li class="nav-item {{ active_class(['designations', 'designations/*']) }}">
            <a href="{{ route('designations.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="briefcase"></i>
              <span class="link-title">Designations</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('roles.view'))
          <li class="nav-item {{ active_class(['roles', 'roles/*']) }}">
            <a href="{{ route('roles.index') }}" class="nav-link">
              <i class="link-icon" data-lucide="shield"></i>
              <span class="link-title">Roles & Permissions</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('settings.bot'))
          <li class="nav-item {{ active_class(['admin/bot-config', 'admin/bot-config/*']) }}">
            <a href="{{ route('bot-config.edit') }}" class="nav-link">
              <i class="link-icon" data-lucide="settings"></i>
              <span class="link-title">Bot Settings</span>
            </a>
          </li>
          @endif

          @if(auth()->user()->canAccess('settings.channels'))
            @if(auth()->user()->company?->bot_usage_type === 'widget')
            <li class="nav-item {{ active_class(['widget', 'widget/*']) }}">
              <a href="{{ route('widgets.index') }}" class="nav-link">
                <i class="link-icon" data-lucide="users"></i>
                <span class="link-title">Widget</span>
              </a>
            </li>
            @endif
            @if(auth()->user()->company?->bot_usage_type === 'whatsapp' || empty(auth()->user()->company?->bot_usage_type))
            <li class="nav-item {{ active_class(['whatsapp-numbers', 'whatsapp-numbers/*']) }}">
              <a href="{{ route('whatsapp-numbers.index') }}" class="nav-link">
                <i class="link-icon" data-lucide="phone"></i>
                <span class="link-title">WhatsApp Numbers</span>
              </a>
            </li>
            @endif
          @endif
        @endif
      @endauth
    </ul>
  </div>
</nav>
