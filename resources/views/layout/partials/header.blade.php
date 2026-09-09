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
      <!-- <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <img src="{{ url('build/images/flags/us.svg') }}" class="w-20px" title="us" alt="flag">
          <span class="ms-2 d-none d-md-inline-block">English</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="languageDropdown">
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/us.svg') }}" class="w-20px" title="us" alt="us"> <span class="ms-2"> English </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/fr.svg') }}" class="w-20px" title="fr" alt="fr"> <span class="ms-2"> French </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/de.svg') }}" class="w-20px" title="de" alt="de"> <span class="ms-2"> German </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/pt.svg') }}" class="w-20px" title="pt" alt="pt"> <span class="ms-2"> Portuguese </span></a>
          <a href="javascript:;" class="dropdown-item py-2 d-flex"><img src="{{ url('build/images/flags/es.svg') }}" class="w-20px" title="es" alt="es"> <span class="ms-2"> Spanish </span></a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="appsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-lucide="layout-grid"></i>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="appsDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p class="mb-0 fw-bold">Web Apps</p>
            <a href="javascript:;" class="text-secondary">Edit</a>
          </div>
          <div class="row g-0 p-1">
            <div class="col-3 text-center">
              <a href="{{ url('/apps/chat') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="message-square" class="icon-lg mb-1"></i><p class="fs-12px">Chat</p></a>
            </div>
            <div class="col-3 text-center">
              <a href="{{ url('/apps/calendar') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="calendar" class="icon-lg mb-1"></i><p class="fs-12px">Calendar</p></a>
            </div>
            <div class="col-3 text-center">
              <a href="{{ url('/email/inbox') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="mail" class="icon-lg mb-1"></i><p class="fs-12px">Email</p></a>
            </div>
            <div class="col-3 text-center">
              <a href="{{ url('/general/profile') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center w-70px h-70px"><i data-lucide="instagram" class="icon-lg mb-1"></i><p class="fs-12px">Profile</p></a>
            </div>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-lucide="mail"></i>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="messageDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p>9 New Messages</p>
            <a href="javascript:;" class="text-secondary">Clear all</a>
          </div>
          <div class="p-1">
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Leonardo Payne</p>
                  <p class="fs-12px text-secondary">Project status</p>
                </div>
                <p class="fs-12px text-secondary">2 min ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Carl Henson</p>
                  <p class="fs-12px text-secondary">Client meeting</p>
                </div>
                <p class="fs-12px text-secondary">30 min ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Jensen Combs</p>
                  <p class="fs-12px text-secondary">Project updates</p>
                </div>
                <p class="fs-12px text-secondary">1 hrs ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Amiah Burton</p>
                  <p class="fs-12px text-secondary">Project deadline</p>
                </div>
                <p class="fs-12px text-secondary">2 hrs ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="d-flex justify-content-between flex-grow-1">
                <div class="me-4">
                  <p>Yaretzi Mayo</p>
                  <p class="fs-12px text-secondary">New record</p>
                </div>
                <p class="fs-12px text-secondary">5 hrs ago</p>
              </div>	
            </a>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i data-lucide="bell"></i>
          <div class="indicator">
            <div class="circle"></div>
          </div>
        </a>
        <div class="dropdown-menu p-0" aria-labelledby="notificationDropdown">
          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <p>6 New Notifications</p>
            <a href="javascript:;" class="text-secondary">Clear all</a>
          </div>
          <div class="p-1">
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                <i class="icon-sm text-white" data-lucide="gift"></i>
              </div>
              <div class="flex-grow-1 me-2">
                <p>New Order Recieved</p>
                <p class="fs-12px text-secondary">30 min ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                <i class="icon-sm text-white" data-lucide="alert-circle"></i>
              </div>
              <div class="flex-grow-1 me-2">
                <p>Server Limit Reached!</p>
                <p class="fs-12px text-secondary">1 hrs ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                <img class="w-30px h-30px rounded-circle" src="{{ url('https://placehold.co/30x30') }}" alt="userr">
              </div>
              <div class="flex-grow-1 me-2">
                <p>New customer registered</p>
                <p class="fs-12px text-secondary">2 sec ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                <i class="icon-sm text-white" data-lucide="layers"></i>
              </div>
              <div class="flex-grow-1 me-2">
                <p>Apps are ready for update</p>
                <p class="fs-12px text-secondary">5 hrs ago</p>
              </div>	
            </a>
            <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
              <div class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                <i class="icon-sm text-white" data-lucide="download"></i>
              </div>
              <div class="flex-grow-1 me-2">
                <p>Download completed</p>
                <p class="fs-12px text-secondary">6 hrs ago</p>
              </div>	
            </a>
          </div>
          <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
            <a href="javascript:;">View all</a>
          </div>
        </div>
      </li> -->
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
            </div>
          </div>
          <ul class="list-unstyled p-1">
            <li>
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
            </li>
            <li>
              <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="dropdown-item py-2 text-body ms-0 w-100 text-start bg-transparent border-0">
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