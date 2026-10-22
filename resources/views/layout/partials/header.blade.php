<nav class="navbar">
  <div class="navbar-content">

    <div class="logo-mini-wrapper">
      <img src="{{ url('build/images/logo.png') }}" class="logo-mini" alt="logo" style="height: 32px; max-width: 130px; object-fit: contain;">
    </div>

    <!-- <form class="search-form">
      <div class="input-group">
        <div class="input-group-text">
          <i data-lucide="search"></i>
        </div>
        <input type="text" class="form-control" id="navbarForm" placeholder="Search here...">
      </div>
    </form> -->

    <ul class="navbar-nav">
      <li class="theme-switcher-wrapper nav-item">
        <input type="checkbox" value="" id="theme-switcher">
        <label for="theme-switcher">
          <div class="box">
            <div class="ball"></div>
            <div class="icons">
              <i class="link-icon" data-lucide="sun"></i>
              <i class="link-icon" data-lucide="moon"></i>
            </div>
          </div>
        </label>
      </li>

      @if(auth()->check() && auth()->user()->role_id === 2)
      <li class="nav-item">
        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#leadsCalendarModal" title="Follow-up Calendar">
          <i data-lucide="calendar"></i>
        </a>
      </li>
      @endif
      {{-- Notifications & Activity Dropdown --}}
      <li class="nav-item dropdown me-2">
        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Notifications & Activities">
          <i data-lucide="bell"></i>
          <span id="headerNotificationDot" class="position-absolute rounded-circle" style="top: 8px; right: 8px; width: 8px; height: 8px; background-color: #ef4444; border: 2px solid #fff; box-sizing: content-box; display: none;"></span>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="notificationDropdown" style="width: 320px; max-width: 90vw;">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <div class="d-flex align-items-center">
              <span class="fw-bold fs-13px me-2">Notifications</span>
              <span id="headerNotificationCountBadge" class="badge bg-danger rounded-pill fs-11px" style="display: none;">0</span>
            </div>
            <a href="javascript:void(0);" id="headerNotificationRefreshBtn" class="text-secondary fs-12px" title="Refresh">
              <i data-lucide="refresh-cw" class="icon-xs"></i>
            </a>
          </div>
          <div class="p-1" id="headerNotificationList" style="max-height: 330px; overflow-y: auto;">
            <div class="p-3 text-center text-secondary fs-12px" id="headerNotificationLoading">
              <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
              Loading activity...
            </div>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top bg-light">
            <a href="{{ route('notifications.index') }}" class="fs-12px fw-semibold text-primary d-flex align-items-center">
              View All Notifications <i data-lucide="chevron-right" class="icon-xs ms-1"></i>
            </a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <img class="w-30px h-30px ms-1 rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="profile">
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
          <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
            <div class="mb-3">
              <img class="w-80px h-80px rounded-circle" src="{{ url('https://placehold.co/80x80') }}" alt="">
            </div>
              <div class="text-center">
                <p class="fs-16px fw-bolder">{{ Auth::user()->name ?? 'Guest' }}</p>
                <p class="fs-12px text-secondary">{{ Auth::user()->email ?? '' }}</p>
                @if(auth()->check())
                    @php
                        $roleDisplay = 'Employee';
                        if (auth()->id() === 1) {
                            $roleDisplay = 'Super Admin';
                        } else {
                            $emp = \App\Models\Employee::where('email', auth()->user()->email)->first();
                            if ($emp && $emp->role === 'ADMIN') {
                                $roleDisplay = 'Company Admin';
                            } elseif (!$emp) {
                                $roleDisplay = 'Company Admin';
                            }
                        }
                    @endphp
                    <span class="badge bg-primary mt-1">{{ $roleDisplay }}</span>
                @endif
              </div>
          </div>
          <ul class="list-unstyled p-1">
            {{-- <li>
              <a href="{{ url('/general/profile') }}" class="dropdown-item py-2 text-body ms-0">
                <i class="me-2 icon-md" data-lucide="user"></i>
                <span>Profile</span>
              </a>
            </li>
            <li>
              <a href="javascript:;" class="dropdown-item py-2 text-body ms-0">
                <i class="me-2 icon-md" data-lucide="edit"></i>
                <span>Edit Profile</span>
              </a>
            </li>
            <li>
              <a href="javascript:;" class="dropdown-item py-2 text-body ms-0">
                <i class="me-2 icon-md" data-lucide="repeat"></i>
                <span>Switch User</span>
              </a>
            </li> --}}
            <li>
              <a href="javascript:void(0);" class="dropdown-item py-2 text-body ms-0" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="me-2 icon-md" data-lucide="key-round"></i>
                <span>Change Password</span>
              </a>
            </li>
            <li class="border-top my-1"></li>
            <li>
              <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="dropdown-item py-2 text-danger ms-0 w-100 text-start bg-transparent border-0">
                  <i class="me-2 icon-md" data-lucide="log-out"></i>
                  <span>Log Out</span>
                </button>
              </form>
            </li>
          </ul>
        </div>
      </li>
    </ul>

    <a href="#" class="sidebar-toggler">
      <i data-lucide="menu"></i>
    </a>

  </div>
</nav>

@if(auth()->check() && auth()->user()->role_id === 2)
{{-- Leads Follow-up Calendar Modal --}}
<div class="modal fade" id="leadsCalendarModal" tabindex="-1" aria-labelledby="leadsCalendarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="leadsCalendarModalLabel">
          <i data-lucide="calendar" class="icon-sm me-2"></i> Leads Follow-up Calendar
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="leadsCalendar"></div>
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('build/plugins/fullcalendar/index.global.min.js') }}"></script>
<style>
  .fc-lead-tooltip {
    position: fixed;
    z-index: 9999;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 220px;
    pointer-events: none;
    font-size: 13px;
    display: none;
  }
  [data-bs-theme="dark"] .fc-lead-tooltip {
    background: #1e293b;
    border-color: #334155;
    color: #e2e8f0;
  }
  .fc-lead-tooltip .tooltip-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
  }
  .fc-lead-tooltip .tooltip-row:last-child { margin-bottom: 0; }
  .fc-lead-tooltip .tooltip-label {
    color: #94a3b8;
    font-size: 11px;
    min-width: 65px;
  }
  .fc-lead-tooltip .tooltip-value { font-weight: 600; }
  .fc-lead-tooltip .tooltip-header {
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 220px;
  }
  [data-bs-theme="dark"] .fc-lead-tooltip .tooltip-header,
  [data-bs-theme="dark"] .fc-lead-tooltip .tooltip-row { border-color: #334155; }
</style>
<div class="fc-lead-tooltip" id="fcLeadTooltip">
  <div class="tooltip-header" id="fcTipName"></div>
  <div class="tooltip-row">
    <span class="tooltip-label">Lead ID</span>
    <span class="tooltip-value" id="fcTipLeadId"></span>
  </div>
  <div class="tooltip-row">
    <span class="tooltip-label">Phone</span>
    <span class="tooltip-value" id="fcTipPhone"></span>
  </div>
  <div class="tooltip-row">
    <span class="tooltip-label">Status</span>
    <span class="tooltip-value" id="fcTipStatus"></span>
  </div>
  <div class="tooltip-row">
    <span class="tooltip-label">Follow-up</span>
    <span class="tooltip-value" id="fcTipDate"></span>
  </div>
  <div class="tooltip-row" id="fcTipTimeRow">
    <span class="tooltip-label">Time</span>
    <span class="tooltip-value" id="fcTipTime"></span>
  </div>
</div>

<script>
  var leadsCalendarEl   = document.getElementById('leadsCalendar');
  var leadsCalendarInit = false;
  var tooltip           = document.getElementById('fcLeadTooltip');

  document.getElementById('leadsCalendarModal').addEventListener('shown.bs.modal', function () {
    if (leadsCalendarInit) return;
    leadsCalendarInit = true;

    var calendar = new FullCalendar.Calendar(leadsCalendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left:   'prev,next today',
        center: 'title',
        right:  'dayGridMonth,timeGridWeek,listMonth'
      },
      height: 600,
      events: '{{ route("leads.calendar-events") }}',
      eventDidMount: function (info) {
        var el = info.el;
        el.style.borderRadius = '20px';
        el.style.padding      = '2px 8px';
        el.style.fontSize     = '11px';
        el.style.fontWeight   = '600';
        el.style.border       = 'none';
        el.style.cursor       = 'pointer';
        var titleEl = el.querySelector('.fc-event-title');
        if (titleEl) {
          titleEl.style.letterSpacing = '0.02em';
        }
      },
      eventClick: function (info) {
        info.jsEvent.preventDefault();
        if (info.event.url) {
          window.location.href = info.event.url;
        }
      },
      eventMouseEnter: function (info) {
        var p       = info.event.extendedProps;
        var date    = info.event.start;
        var dateStr = date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        var hasTime = (date.getHours() !== 0 || date.getMinutes() !== 0);
        var timeStr = hasTime
          ? date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })
          : null;

        document.getElementById('fcTipName').textContent   = info.event.title;
        document.getElementById('fcTipLeadId').textContent = p.lead_id;
        document.getElementById('fcTipPhone').textContent  = p.phone;
        document.getElementById('fcTipStatus').textContent = p.status;
        document.getElementById('fcTipDate').textContent   = dateStr;

        var timeRow = document.getElementById('fcTipTimeRow');
        if (timeStr) {
          document.getElementById('fcTipTime').textContent = timeStr;
          timeRow.style.display = 'flex';
        } else {
          timeRow.style.display = 'none';
        }

        tooltip.style.display = 'block';
        positionTooltip(info.jsEvent);
      },
      eventMouseLeave: function () {
        tooltip.style.display = 'none';
      }
    });

    calendar.render();
  });

  document.addEventListener('mousemove', function (e) {
    if (tooltip.style.display === 'block') {
      positionTooltip(e);
    }
  });

  function positionTooltip(e) {
    var x = e.clientX + 14;
    var y = e.clientY + 14;
    if (x + 260 > window.innerWidth)  x = e.clientX - 260;
    if (y + 180 > window.innerHeight) y = e.clientY - 180;
    tooltip.style.left = x + 'px';
    tooltip.style.top  = y + 'px';
  }
</script>
@endif

{{-- Change Password Modal --}}
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom py-3">
        <h6 class="modal-title fw-bold d-flex align-items-center" id="changePasswordModalLabel">
          <i data-lucide="key-round" class="icon-sm text-primary me-2"></i> Change Password
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('password.update') }}" id="changePasswordForm">
        @csrf
        @method('put')
        <div class="modal-body py-3">
          <p class="text-muted small mb-3">Ensure your account is using a long, random password to stay secure.</p>

          <div class="mb-3">
            <label class="form-label small fw-bold">Current Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="current_password" id="current_password" class="form-control form-control-sm @error('current_password', 'updatePassword') is-invalid @enderror" required autocomplete="current-password" placeholder="Enter current password">
              <button class="btn btn-outline-secondary btn-sm toggle-pwd-visibility" type="button" data-target="current_password">
                <i data-lucide="eye" class="icon-xs"></i>
              </button>
            </div>
            @error('current_password', 'updatePassword')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">New Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="password" id="new_password" class="form-control form-control-sm @error('password', 'updatePassword') is-invalid @enderror" required autocomplete="new-password" placeholder="Minimum 8 characters">
              <button class="btn btn-outline-secondary btn-sm toggle-pwd-visibility" type="button" data-target="new_password">
                <i data-lucide="eye" class="icon-xs"></i>
              </button>
            </div>
            @error('password', 'updatePassword')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-2">
            <label class="form-label small fw-bold">Confirm New Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="password_confirmation" id="new_password_confirmation" class="form-control form-control-sm @error('password_confirmation', 'updatePassword') is-invalid @enderror" required autocomplete="new-password" placeholder="Re-enter new password">
              <button class="btn btn-outline-secondary btn-sm toggle-pwd-visibility" type="button" data-target="new_password_confirmation">
                <i data-lucide="eye" class="icon-xs"></i>
              </button>
            </div>
            @error('password_confirmation', 'updatePassword')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer border-top py-2 bg-light">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-primary fw-bold">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Show modal automatically if validation failed from password update
    @if($errors->updatePassword->any())
      var pwdModalEl = document.getElementById('changePasswordModal');
      if (pwdModalEl && typeof bootstrap !== 'undefined') {
        var pwdModal = new bootstrap.Modal(pwdModalEl);
        pwdModal.show();
      }
    @endif

    // Toggle password visibility
    document.querySelectorAll('.toggle-pwd-visibility').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetId = this.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (input) {
          if (input.type === 'password') {
            input.type = 'text';
            this.innerHTML = '<i data-lucide="eye-off" class="icon-xs"></i>';
          } else {
            input.type = 'password';
            this.innerHTML = '<i data-lucide="eye" class="icon-xs"></i>';
          }
          if (window.lucide) {
            lucide.createIcons();
          }
        }
      });
    });
  });
</script>