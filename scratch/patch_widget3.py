import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update loadHistory
hist_target = """d.messages.forEach(function (m) { addMessage(m.text, m.from, m.sender_name, { url: m.media_url, fileName: m.file_name }); });"""
hist_replacement = """d.messages.forEach(function (m) { addMessage(m.text, m.from, m.sender_name, { url: m.media_url, fileName: m.file_name }, m.sent_at); });"""
content = content.replace(hist_target, hist_replacement)

# 2. Update WebSocket NewMessage event
ws_target = """addMessage(msg.message_text, 'bot', msg.sender_name, { url: msg.media_url, fileName: msg.file_name });"""
ws_replacement = """addMessage(msg.message_text, 'bot', msg.sender_name, { url: msg.media_url, fileName: msg.file_name }, msg.sent_at);"""
content = content.replace(ws_target, ws_replacement)

# 3. Update addMessage signature and sender label logic
add_msg_target = """  function addMessage(text, who, senderName, attachment) {
    if (!text && !(attachment && attachment.url)) return null; // nothing to show
    var row = document.createElement('div');
    row.className = 'ws-row ws-' + who;
    var wrap = document.createElement('div');
    wrap.className = 'ws-msg-wrap';
    if (senderName) {
      var label = document.createElement('div');
      label.className = 'ws-sender-label';
      label.textContent = senderName;
      wrap.appendChild(label);
    }"""
add_msg_replacement = """  function addMessage(text, who, senderName, attachment, sentAt) {
    if (!text && !(attachment && attachment.url)) return null; // nothing to show
    sentAt = sentAt || new Date().toISOString();
    var row = document.createElement('div');
    row.className = 'ws-row ws-' + who;
    var wrap = document.createElement('div');
    wrap.className = 'ws-msg-wrap';
    
    var timeStr = '';
    var d = new Date(sentAt);
    if (!isNaN(d.getTime())) {
        var hours = d.getHours();
        var minutes = d.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        timeStr = hours + ':' + minutes + ' ' + ampm;
    }
    
    if (senderName || timeStr) {
      var label = document.createElement('div');
      label.className = 'ws-sender-label';
      var parts = [];
      if (senderName) parts.push(senderName);
      if (timeStr) parts.push(timeStr);
      label.textContent = parts.join(' • ');
      wrap.appendChild(label);
    }"""
content = content.replace(add_msg_target, add_msg_replacement)


with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
