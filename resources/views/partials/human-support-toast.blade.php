{{--
  Instant, app-wide "a customer wants a human" alert - fires for BOTH
  the website widget and WhatsApp.

  Include this ONCE in your main layout, right before </body>, e.g.:
      @include('partials.human-support-toast')

  It needs nothing else - no new routes, no JS bundle changes. It reuses
  the SAME window.Echo connection your existing conversation pages
  already use (see resources/js/echo.js), just listening on a new
  channel ('human-support') that HumanSupportRequested.php broadcasts
  on. Works on every authenticated page since the layout loads on all
  of them, not just the conversation show page.

  Currently a single global channel (no per-company scoping) since real
  multi-tenant login isn't fully wired for this specific alert yet -
  every logged-in CRM user sees every request, same as
  HumanSupportRequested.php's own doc comment explains.
--}}
<div id="hs-toast-container" style="position:fixed;top:16px;right:16px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:360px;"></div>

<style>
  .hs-toast {
    background: #111827;
    color: #fff;
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    font-family: sans-serif;
    animation: hs-toast-in .25s ease-out;
    border-left: 4px solid #ef4444;
  }
  @keyframes hs-toast-in {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
  }
  .hs-toast-title {
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
  }
  .hs-toast-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ef4444;
    animation: hs-toast-pulse 1.2s infinite;
    flex-shrink: 0;
  }
  @keyframes hs-toast-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .3; }
  }
  .hs-toast-body {
    font-size: 13px;
    opacity: .9;
    margin-bottom: 10px;
    line-height: 1.4;
    word-break: break-word;
  }
  .hs-toast-actions {
    display: flex;
    gap: 8px;
  }
  .hs-toast-btn {
    background: #fff;
    color: #111827;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
  }
  .hs-toast-dismiss {
    background: transparent;
    color: #fff;
    opacity: .7;
    border: none;
    font-size: 12px;
    cursor: pointer;
    padding: 6px 8px;
  }
</style>

<script>
(function () {
  if (typeof window.Echo === 'undefined') {
    console.error('[human-support-toast] window.Echo is not defined - alerts will not work.');
    return;
  }

  var container = document.getElementById('hs-toast-container');

  function beep() {
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
      osc.start();
      osc.stop(ctx.currentTime + 0.4);
    } catch (e) { /* audio not available - not worth failing over */ }
  }

  function showToast(data) {
    beep();
    var el = document.createElement('div');
    el.className = 'hs-toast';
    el.innerHTML =
      '<div class="hs-toast-title"><span class="hs-toast-dot"></span> ' +
      (data.company_name || 'A customer') + ' wants a human</div>' +
      '<div class="hs-toast-body"></div>' +
      '<div class="hs-toast-actions">' +
        '<a class="hs-toast-btn" href="' + data.url + '">Open conversation</a>' +
        '<button class="hs-toast-dismiss" type="button">Dismiss</button>' +
      '</div>';
    el.querySelector('.hs-toast-body').textContent = data.message_text || '';
    el.querySelector('.hs-toast-dismiss').addEventListener('click', function () { el.remove(); });
    container.appendChild(el);
    setTimeout(function () { el.remove(); }, 30000); // auto-dismiss after 30s if ignored
  }

  window.Echo.channel('human-support')
    .listen('HumanSupportRequested', function (e) {
      showToast(e);
    });
})();
</script>
