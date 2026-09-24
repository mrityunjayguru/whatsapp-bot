import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# We already ran the first part maybe?
# Let's check if the replacement is already there
replacement = """      fetch(LARAVEL_BASE + '/api/widget/history', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: CFG.token, session_id: sessionId })
      })
        .then(function (r) { 
          if (!r.ok && (r.status === 404 || r.status === 403)) {
            var btn = document.getElementById('trp-widget-btn');
            var win = document.getElementById('trp-widget-window');
            if (btn) btn.style.display = 'none';
            if (win) win.style.display = 'none';
            throw new Error("Widget inactive");
          }
          return r.json(); 
        })"""

if "throw new Error(\"Widget inactive\")" not in content:
    target = """      fetch(LARAVEL_BASE + '/api/widget/history', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: CFG.token, session_id: sessionId })
      })
        .then(function (r) { return r.json(); })"""
    content = content.replace(target, replacement)

catch_replacement = """        .catch(function (err) {
          if (err.message === "Widget inactive") return;
          // History fetch failing shouldn't block opening the chat at all
          // - just fall back to a fresh welcome message.
          if (CFG.welcomeMessage) addMessage(CFG.welcomeMessage, 'bot');
        });"""

if "if (err.message === \"Widget inactive\") return;" not in content:
    catch_target = """        .catch(function () {
          // History fetch failing shouldn't block opening the chat at all
          // - just fall back to a fresh welcome message.
          if (CFG.welcomeMessage) addMessage(CFG.welcomeMessage, 'bot');
        });"""
    content = content.replace(catch_target, catch_replacement)

ping_code = """
  // Check if widget is active on load to hide button if expired
  fetch(LARAVEL_BASE + '/api/widget/history', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ token: CFG.token, session_id: getSessionId() })
  }).then(function(r) {
    if (!r.ok && (r.status === 404 || r.status === 403)) {
      var btn = document.getElementById('trp-widget-btn');
      if (btn) btn.style.display = 'none';
    }
  }).catch(function(){});
"""

if "Check if widget is active on load" not in content:
    content = content.replace("  })();\n", ping_code + "\n  })();\n")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
