
(function () {
  var CFG = {"botName": "TRP Bot", "welcomeMessage": "Hi! How can I assist you today?", "primaryColor": "#111212", "buttonPosition": "bottom-right", "token": "84fdf6e7c9ab4753", "reverbKey": "hvrhvpmnfv77h3vvn2sh", "reverbHost": "chat.designdemonz.com", "reverbPort": 443, "reverbScheme": "https"};

  // Plain, always-inspectable connection status - type
  // `window.__widgetDebug` directly into the DevTools console prompt
  // (not the filter box) at any point to see exactly what's happening,
  // no log-filtering needed. Persists across widget open/close since
  // it's on window, not inside this closure.
  window.__widgetDebug = window.__widgetDebug || {
    status: 'script_loaded',
    pusherLoaded: false,
    pusherVersion: null,
    subscribeCalledFor: null,
    subscribedChannel: null,
    wsTarget: null,
    connectionStates: [],
    lastError: null,
    lastMessageReceived: null,
  };

  var API_BASE = (function () {
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
      var src = scripts[i].src || '';
      var idx = src.indexOf('/widget/widget.js');
      if (idx !== -1) return src.substring(0, idx);
    }
    return '';
  })();

  // The Laravel CRM and the Python bot share one domain, with the bot
  // mounted under a path prefix (nginx proxy_pass) - API_BASE above ends
  // in that prefix (e.g. "https://chat.designdemonz.com/pybot"). Strip
  // it to get Laravel's own base, since visitor messages now go through
  // Laravel first (for persistence + real-time), which then calls the
  // bot internally - see WidgetMessageController.php. If you ever change
  // the nginx prefix from "/pybot", update PYBOT_PREFIX below to match.
  var PYBOT_PREFIX = '/pybot';
  var LARAVEL_BASE = API_BASE.slice(-PYBOT_PREFIX.length) === PYBOT_PREFIX
    ? API_BASE.slice(0, -PYBOT_PREFIX.length)
    : API_BASE;

  var C = CFG.primaryColor;
  var side = CFG.buttonPosition === 'bottom-left' ? 'left' : 'right';

  var style = document.createElement('style');
  style.textContent = [
    '#ws-btn{position:fixed;width:56px;height:56px;border-radius:50%;background:' + C + ';',
    'border:none;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.25);display:flex;',
    'align-items:center;justify-content:center;z-index:2147483000;bottom:24px;' + side + ':24px;',
    'transition:transform .2s}',
    '#ws-btn:hover{transform:scale(1.08)}',
    '#ws-btn svg{width:26px;height:26px;fill:#fff}',

    '#ws-panel{position:fixed;bottom:92px;' + side + ':24px;width:360px;max-width:calc(100vw - 32px);',
    'height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;',
    'box-shadow:0 12px 48px rgba(0,0,0,.18);display:flex;flex-direction:column;',
    'z-index:2147483001;overflow:hidden;opacity:0;pointer-events:none;',
    'transform:translateY(16px) scale(.97);transition:opacity .2s,transform .2s}',
    '#ws-panel.ws-open{opacity:1;pointer-events:all;transform:none}',

    '#ws-head{background:' + C + ';color:#fff;padding:14px 16px}',
    '#ws-head h3{margin:0;font:700 15px sans-serif}',
    '#ws-head small{display:block;font:400 12px sans-serif;opacity:.85;margin-top:2px}',
    '#ws-close{position:absolute;top:12px;right:14px;background:none;border:none;color:#fff;',
    'font-size:20px;cursor:pointer;opacity:.85;line-height:1}',

    '#ws-msgs{flex:1;overflow-y:auto;padding:16px 12px;display:flex;flex-direction:column;gap:10px;',
    'font-family:sans-serif;background:#f8fafc}',
    '.ws-row{display:flex}',
    '.ws-row.ws-user{justify-content:flex-end}',
    '.ws-msg-wrap{display:flex;flex-direction:column;max-width:78%}',
    '.ws-msg-wrap .ws-bub{max-width:100%}',
    '.ws-sender-label{font-size:11px;font-weight:600;color:#64748b;margin:0 4px 2px}',
    '.ws-row.ws-user .ws-sender-label{text-align:right}',
    '.ws-bub{max-width:78%;padding:9px 13px;border-radius:14px;font-size:14px;line-height:1.5;',
    'word-break:break-word;white-space:pre-wrap}',
    '.ws-attachment{display:flex;align-items:center;gap:8px;text-decoration:none;font-weight:600}',
    '.ws-row.ws-bot .ws-attachment{color:' + C + '}',
    '.ws-row.ws-user .ws-attachment{color:#fff}',
    '.ws-row.ws-bot .ws-bub{background:#fff;color:#0f172a;border:1px solid #e2e8f0;border-bottom-left-radius:4px}',
    '.ws-row.ws-user .ws-bub{background:' + C + ';color:#fff;border-bottom-right-radius:4px}',
    '.ws-row.ws-event{justify-content:center;margin:8px 0}',
    '.ws-event-bub{background:#e2e8f0;color:#000;font-size:12px;font-weight:bold;padding:4px 12px;border-radius:12px;text-align:center}',
    '.ws-typing{display:flex;gap:4px;padding:10px 13px}',
    '.ws-typing span{width:6px;height:6px;border-radius:50%;background:#94a3b8;',
    'animation:ws-bounce 1.2s infinite}',
    '.ws-typing span:nth-child(2){animation-delay:.15s}',
    '.ws-typing span:nth-child(3){animation-delay:.3s}',
    '@keyframes ws-bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-4px)}}',

    '#ws-form{display:flex;align-items:center;gap:8px;border-top:1px solid #e2e8f0;',
    'padding:10px 12px;background:#fff}',
    '#ws-input{flex:1;border:none;outline:none;font:400 14px sans-serif;padding:6px 2px;color:#0f172a}',
    '#ws-send{width:34px;height:34px;border-radius:50%;background:' + C + ';border:none;',
    'cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}',
    '#ws-send svg{width:16px;height:16px;fill:#fff}',
    '#ws-send:disabled{opacity:.5;cursor:not-allowed}',

    '.ws-options{display:flex;flex-direction:column;gap:6px;margin-top:2px;padding:0 2px}',
    '.ws-option-btn{text-align:left;background:#fff;border:1px solid ' + C + ';color:' + C + ';',
    'border-radius:10px;padding:8px 12px;font:500 13px sans-serif;cursor:pointer;',
    'transition:background .15s,color .15s}',
    '.ws-option-btn:hover{background:' + C + ';color:#fff}',
  ].join('');
  document.head.appendChild(style);

  var btn = document.createElement('button');
  btn.id = 'ws-btn';
  btn.setAttribute('aria-label', 'Open chat');
  btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
  document.body.appendChild(btn);

  var panel = document.createElement('div');
  panel.id = 'ws-panel';
  panel.innerHTML =
    '<div id="ws-head">' +
      '<h3>' + esc(CFG.botName) + '</h3>' +
      '<small>Usually replies instantly</small>' +
      '<button id="ws-close" aria-label="Close chat">\u00d7</button>' +
    '</div>' +
    '<div id="ws-msgs"></div>' +
    '<div id="ws-form">' +
      '<input id="ws-input" type="text" placeholder="Type a message..." />' +
      '<button id="ws-send" aria-label="Send"><svg viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg></button>' +
    '</div>';
  document.body.appendChild(panel);

  var msgsEl = document.getElementById('ws-msgs');
  var inputEl = document.getElementById('ws-input');
  var sendEl = document.getElementById('ws-send');

  var isOpen = false;
  var greeted = false;
  var sessionId = null;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function getSessionId() {
    try {
      var key = 'ws_session_' + CFG.token;
      var existing = window.localStorage.getItem(key);
      if (existing) return existing;
      var id = 'sess_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
      window.localStorage.setItem(key, id);
      return id;
    } catch (e) {
      return 'sess_' + Math.random().toString(36).slice(2);
    }
  }

  function addMessage(text, who, senderName, attachment, isEvent) {
    if (!text && !(attachment && attachment.url)) return null; // nothing to show
    var row = document.createElement('div');
    
    if (isEvent) {
      row.className = 'ws-row ws-event';
      var bub = document.createElement('div');
      bub.className = 'ws-event-bub';
      bub.textContent = text;
      row.appendChild(bub);
    } else {
      row.className = 'ws-row ws-' + who;
      var wrap = document.createElement('div');
      wrap.className = 'ws-msg-wrap';
      if (senderName) {
        var label = document.createElement('div');
        label.className = 'ws-sender-label';
        label.textContent = senderName;
        wrap.appendChild(label);
      }
      if (text) {
        var bub = document.createElement('div');
        bub.className = 'ws-bub';
        bub.textContent = text;
        wrap.appendChild(bub);
      }
      if (attachment && attachment.url) {
        var att = document.createElement('a');
        att.className = 'ws-bub ws-attachment';
        att.href = attachment.url;
        att.target = '_blank';
        att.rel = 'noopener noreferrer';
        att.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" style="flex-shrink:0"><path fill="currentColor" d="M12 2a1 1 0 011 1v10.59l3.3-3.3a1 1 0 111.4 1.42l-5 5a1 1 0 01-1.4 0l-5-5a1 1 0 111.4-1.42l3.3 3.3V3a1 1 0 011-1zM5 19a1 1 0 100 2h14a1 1 0 100-2H5z"/></svg><span></span>';
        att.querySelector('span').textContent = attachment.fileName || 'Download file';
        wrap.appendChild(att);
      }
      row.appendChild(wrap);
    }
    msgsEl.appendChild(row);
    msgsEl.scrollTop = msgsEl.scrollHeight;
    return row;
  }

  function addOptions(options) {
    var wrap = document.createElement('div');
    wrap.className = 'ws-options';
    options.forEach(function (opt) {
      var btn = document.createElement('button');
      btn.className = 'ws-option-btn';
      btn.textContent = opt.title;
      btn.addEventListener('click', function () {
        wrap.remove();
        selectOption(opt.id, opt.title);
      });
      wrap.appendChild(btn);
    });
    msgsEl.appendChild(wrap);
    msgsEl.scrollTop = msgsEl.scrollHeight;
  }

  function selectOption(id, title) {
    addMessage(title, 'user');
    showTyping();
    fetch(LARAVEL_BASE + '/api/widget/select', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: CFG.token, session_id: sessionId, source_id: id, title: title })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        hideTyping();
        if (d.reply) addMessage(d.reply, 'bot');
        if (d.conversation_id) subscribeToConversation(d.conversation_id);
      })
      .catch(function () {
        hideTyping();
        addMessage("That didn't go through. Please try again.", 'bot');
      });
  }

  var typingRow = null;
  function showTyping() {
    typingRow = document.createElement('div');
    typingRow.className = 'ws-row ws-bot';
    typingRow.innerHTML = '<div class="ws-bub ws-typing"><span></span><span></span><span></span></div>';
    msgsEl.appendChild(typingRow);
    msgsEl.scrollTop = msgsEl.scrollHeight;
  }
  function hideTyping() {
    if (typingRow) { typingRow.remove(); typingRow = null; }
  }

  function send(text) {
    text = (text || '').trim();
    if (!text) return;
    addMessage(text, 'user');
    inputEl.value = '';
    inputEl.disabled = true;
    sendEl.disabled = true;
    showTyping();

    fetch(LARAVEL_BASE + '/api/widget/message', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: CFG.token, session_id: sessionId, message: text })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        hideTyping();
        if (d.reply) addMessage(d.reply, 'bot');
        if (d.options && d.options.length) addOptions(d.options);
        if (d.conversation_id) subscribeToConversation(d.conversation_id);
      })
      .catch(function () {
        hideTyping();
        addMessage("That didn't go through. Please try again.", 'bot');
      })
      .then(function () {
        inputEl.disabled = false;
        sendEl.disabled = false;
        inputEl.focus();
      });
  }

  // --- Real-time: so a human agent's reply shows up live, without the
  // visitor needing to send another message or reload the page. Reuses
  // the CRM's existing Reverb/Pusher setup (same one its own Inbox UI
  // connects to) rather than building anything new - loads pusher-js
  // from a CDN (a hand-rolled raw WebSocket client would risk subtly
  // getting the ping/reconnect handling wrong; this is the same library
  // the CRM's own frontend already relies on). Only subscribes once the
  // visitor has sent a message and we know their conversation_id - a
  // conversation that already existed before this page load (e.g. the
  // visitor closed the tab and an agent replied later) isn't resumed
  // automatically in this version.
  var pusherClient = null;
  var subscribedConversationId = null;

  function subscribeToConversation(conversationId) {
    if (subscribedConversationId === conversationId) return;
    subscribedConversationId = conversationId;
    window.__widgetDebug.subscribeCalledFor = conversationId;

    function doSubscribe() {
      // Deliberately NOT trusting an existing window.Pusher here - on a
      // page that already has its own (different, incompatible) Pusher
      // setup baked into another bundle, window.Pusher can already be
      // defined by THAT code before this ever runs. Reusing it silently
      // produces a client that never actually connects (no VERSION, no
      // connection state changes at all) - this is exactly what was
      // happening. window.__widgetPusherClass is a name unique to this
      // widget that nothing else on any page would ever set, so it's
      // guaranteed to be OUR OWN freshly-loaded copy every time.
      if (!window.__widgetPusherClass) {
        window.__widgetDebug.status = 'pusher_script_missing';
        console.error('[widget] Pusher script failed to load - real-time agent replies will not work.');
        return;
      }
      window.__widgetDebug.pusherLoaded = true;
      window.__widgetDebug.pusherVersion = window.__widgetPusherClass.VERSION;
      if (!CFG.reverbKey || !CFG.reverbHost) {
        // This is the actual cause when a human agent's reply never
        // shows up live: the bot's own replies come back in the plain
        // HTTP response and don't need this connection at all, so that
        // working proves nothing about whether THIS is configured -
        // this specific case (a blank key/host) means the Python
        // service's REVERB_APP_KEY / REVERB_HOST env vars were never
        // actually set.
        window.__widgetDebug.status = 'missing_reverb_config';
        console.error('[widget] Reverb key/host missing from widget config - cannot connect for real-time agent replies.', CFG);
        return;
      }
      if (!pusherClient) {
        window.__widgetDebug.status = 'connecting';
        window.__widgetDebug.wsTarget = (CFG.reverbScheme === 'https' ? 'wss://' : 'ws://') + CFG.reverbHost + ':' + CFG.reverbPort;
        try {
          pusherClient = new window.__widgetPusherClass(CFG.reverbKey, {
            // Reverb doesn't use Pusher.com's "cluster" concept at all,
            // but this client library's constructor still refuses to run
            // without SOME value present here - it was throwing
            // synchronously on every attempt ("Options object must
            // provide a cluster"), before ever reaching the connection
            // listeners below, which is why status never advanced past
            // "connecting" and connectionStates stayed empty. The value
            // itself is a placeholder Reverb ignores entirely.
            cluster: 'mt1',
            wsHost: CFG.reverbHost,
            wsPort: CFG.reverbPort,
            wssPort: CFG.reverbPort,
            forceTLS: CFG.reverbScheme === 'https',
            enabledTransports: ['ws', 'wss'],
            disableStats: true
          });
        } catch (e) {
          window.__widgetDebug.status = 'constructor_threw';
          window.__widgetDebug.lastError = String(e);
          console.error('[widget] Pusher constructor threw:', e);
          return;
        }
        pusherClient.connection.bind('connected', function () {
          window.__widgetDebug.status = 'connected';
          console.log('[widget] Real-time connected.');
        });
        pusherClient.connection.bind('error', function (err) {
          window.__widgetDebug.status = 'connection_error';
          window.__widgetDebug.lastError = err;
          console.error('[widget] Real-time connection error:', err);
        });
        pusherClient.connection.bind('state_change', function (states) {
          window.__widgetDebug.connectionStates = window.__widgetDebug.connectionStates || [];
          window.__widgetDebug.connectionStates.push(states.previous + ' -> ' + states.current);
        });
      }
      var channel = pusherClient.subscribe('conversation.' + conversationId);
      channel.bind('pusher:subscription_succeeded', function () {
        window.__widgetDebug.status = 'subscribed';
        window.__widgetDebug.subscribedChannel = 'conversation.' + conversationId;
        console.log('[widget] Subscribed to conversation.' + conversationId);
      });
      channel.bind('pusher:subscription_error', function (err) {
        window.__widgetDebug.status = 'subscription_error';
        window.__widgetDebug.lastError = err;
      });
      channel.bind('App\\Events\\NewMessage', function (data) {
        var msg = data && data.message;
        window.__widgetDebug.lastMessageReceived = msg;
        console.log('[widget] Real-time message received:', msg);
        
        var isEvent = msg.is_conversation_event === true || msg.is_conversation_event === 1;
        var txt = msg.message_text || "";
        if (txt.endsWith('joined conversation') || txt.toLowerCase().startsWith('ended conversation') || txt.startsWith('AI Support is now assisting')) {
            isEvent = true;
        }

        // Only show human agent replies here - the bot's own reply is
        // already shown synchronously from the fetch() response above,
        // so echoing it again here would show it twice.
        if (msg && msg.direction === 'OUTBOUND' && (msg.sender_type === 'EMPLOYEE' || msg.sender_type === 'SYSTEM' || isEvent)) {
          addMessage(msg.message_text, 'bot', msg.sender_name, { url: msg.media_url, fileName: msg.file_name }, isEvent);
        }
      });
    }

    if (window.__widgetPusherClass) {
      doSubscribe();
      return;
    }
    var s = document.createElement('script');
    s.src = 'https://js.pusher.com/8.4.0/pusher.min.js';
    s.onload = function () {
      // Capture OUR loaded copy immediately, into a name nothing else on
      // the page could ever collide with - this is what actually fixes
      // the bug: even if some other script on this page also sets
      // window.Pusher (before or after this), our own connection always
      // uses THIS specific, known-good reference, never the ambient
      // global.
      window.__widgetPusherClass = window.Pusher;
      doSubscribe();
    };
    document.head.appendChild(s);
  }

  function loadHistory() {
    fetch(LARAVEL_BASE + '/api/widget/history', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: CFG.token, session_id: sessionId })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (CFG.welcomeMessage) {
            addMessage(CFG.welcomeMessage, 'bot');
          }
          if (d.messages && d.messages.length) {
            d.messages.forEach(function (m) { addMessage(m.text, m.from, m.sender_name, { url: m.media_url, fileName: m.file_name }, m.is_conversation_event); });
            if (d.conversation_id) subscribeToConversation(d.conversation_id);
          }
      })
      .catch(function () {
        // History fetch failing shouldn't block opening the chat at all
        // - just fall back to a fresh welcome message.
        if (CFG.welcomeMessage) addMessage(CFG.welcomeMessage, 'bot');
      });
  }

  function endChat() {
    if (!confirm('End this chat? Starting a new message afterwards will begin a new conversation.')) return;
    fetch(LARAVEL_BASE + '/api/widget/close', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: CFG.token, session_id: sessionId })
    }).finally(function () {
      msgsEl.innerHTML = '';
      greeted = false;
      if (pusherClient && subscribedConversationId) {
        pusherClient.unsubscribe('conversation.' + subscribedConversationId);
      }
      subscribedConversationId = null;
      loadHistory(); // conversation is now CLOSED, so this shows the welcome message fresh
    });
  }

  function open() {
    isOpen = true;
    panel.classList.add('ws-open');
    inputEl.focus();
    if (!greeted) {
      greeted = true;
      sessionId = getSessionId();
      loadHistory();
    }
  }
  function close() {
    isOpen = false;
    panel.classList.remove('ws-open');
  }

  btn.addEventListener('click', function () { isOpen ? close() : open(); });
  document.getElementById('ws-close').addEventListener('click', close);
  sendEl.addEventListener('click', function () { send(inputEl.value); });
  inputEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); send(inputEl.value); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) close();
  });
})();
