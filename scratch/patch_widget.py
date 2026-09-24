import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update the CSS for the attachment button and the filename preview
css_target = """    '#ws-input{flex:1;border:none;outline:none;font:400 14px sans-serif;padding:6px 2px;color:#0f172a}',"""
css_replacement = """    '#ws-input{flex:1;border:none;outline:none;font:400 14px sans-serif;padding:6px 2px;color:#0f172a}',
    '#ws-attach-label{cursor:pointer; display:flex; align-items:center; justify-content:center; width:34px; height:34px;}',
    '#ws-attach-label svg{width:20px;height:20px;fill:#64748b;transition:fill .2s}',
    '#ws-attach-label:hover svg{fill:' + C + '}',
    '#ws-file-preview{font-size:12px; color:#64748b; margin-bottom:6px; display:none;}',
"""
content = content.replace(css_target, css_replacement)

# 2. Update the HTML layout in #ws-panel
html_target = """    '<div id="ws-form">' +
      '<input id="ws-input" type="text" placeholder="Type a message..." />' +
      '<button id="ws-send" aria-label="Send"><svg viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg></button>' +
    '</div>';"""

html_replacement = """    '<div id="ws-file-preview"></div>' +
    '<div id="ws-form">' +
      '<label id="ws-attach-label" for="ws-file" aria-label="Attach File"><svg viewBox="0 0 24 24"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg></label>' +
      '<input type="file" id="ws-file" style="display:none;" />' +
      '<input id="ws-input" type="text" placeholder="Type a message..." />' +
      '<button id="ws-send" aria-label="Send"><svg viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg></button>' +
    '</div>';"""
content = content.replace(html_target, html_replacement)

# 3. Handle file input change and send method with FormData
js_setup_target = """  var msgsEl = document.getElementById('ws-msgs');
  var inputEl = document.getElementById('ws-input');
  var sendEl = document.getElementById('ws-send');"""
js_setup_replacement = """  var msgsEl = document.getElementById('ws-msgs');
  var inputEl = document.getElementById('ws-input');
  var sendEl = document.getElementById('ws-send');
  var fileEl = document.getElementById('ws-file');
  var previewEl = document.getElementById('ws-file-preview');

  var attachedFile = null;

  fileEl.addEventListener('change', function(e) {
    if (e.target.files.length > 0) {
      attachedFile = e.target.files[0];
      previewEl.textContent = 'Attached: ' + attachedFile.name;
      previewEl.style.display = 'block';
    } else {
      attachedFile = null;
      previewEl.style.display = 'none';
    }
  });"""
content = content.replace(js_setup_target, js_setup_replacement)

# 4. Modify the `send` method to use FormData when a file is attached
send_method_target = """  function send(text) {
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
    })"""
send_method_replacement = """  function send(text) {
    text = (text || '').trim();
    if (!text && !attachedFile) return;
    
    // Show what user sent
    if (attachedFile) {
        addMessage(text, 'user', null, { fileName: attachedFile.name, url: '#' });
    } else {
        addMessage(text, 'user');
    }
    
    var f = attachedFile;
    attachedFile = null;
    fileEl.value = '';
    previewEl.style.display = 'none';

    inputEl.value = '';
    inputEl.disabled = true;
    sendEl.disabled = true;
    showTyping();

    var fetchOpts;
    if (f) {
      var fd = new FormData();
      fd.append('token', CFG.token);
      fd.append('session_id', sessionId);
      fd.append('message', text);
      fd.append('attachment', f);
      fetchOpts = {
        method: 'POST',
        body: fd
      };
    } else {
      fetchOpts = {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: CFG.token, session_id: sessionId, message: text })
      };
    }

    fetch(LARAVEL_BASE + '/api/widget/message', fetchOpts)"""
content = content.replace(send_method_target, send_method_replacement)

# 5. Modify addMessage to render hyperlinks
add_message_target = """    if (text) {
      var bub = document.createElement('div');
      bub.className = 'ws-bub';
      bub.textContent = text;
      wrap.appendChild(bub);
    }"""
add_message_replacement = """    if (text) {
      var bub = document.createElement('div');
      bub.className = 'ws-bub';
      
      var urlRegex = /(https?:\\/\\/[^\\s]+)/g;
      var htmlText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
      htmlText = htmlText.replace(urlRegex, function(url) {
          return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">' + url + '</a>';
      });
      bub.innerHTML = htmlText;
      
      wrap.appendChild(bub);
    }"""
content = content.replace(add_message_target, add_message_replacement)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
