import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Update addMessage signature and content
old_sig = "function addMessage(text, who, senderName, attachment, sentAt, senderRole) {"
new_sig = "function addMessage(text, who, senderName, attachment, sentAt, senderRole, isEvent) {"

content = content.replace(old_sig, new_sig)

old_row = "    var row = document.createElement('div');\n    row.className = 'ws-row ws-' + who;"
new_row = "    var row = document.createElement('div');\n    if (isEvent) {\n      row.className = 'ws-row ws-event';\n      var bub = document.createElement('div');\n      bub.className = 'ws-event-bub';\n      bub.textContent = text;\n      row.appendChild(bub);\n      msgsEl.appendChild(row);\n      msgsEl.scrollTop = msgsEl.scrollHeight;\n      return row;\n    }\n    row.className = 'ws-row ws-' + who;"

content = content.replace(old_row, new_row)

# Update the websocket NewMessage listener
old_ws = """        if (msg && msg.direction === 'OUTBOUND' && (msg.sender_type === 'EMPLOYEE' || msg.sender_type === 'SYSTEM')) {
          addMessage(msg.message_text, 'bot', msg.sender_name, { url: msg.media_url, fileName: msg.file_name }, msg.sent_at);
        }"""

new_ws = """        var isEvent = msg.is_conversation_event === true || msg.is_conversation_event === 1;
        var txt = msg.message_text || "";
        if (txt.endsWith('joined conversation') || txt.startsWith('ended conversation') || txt.startsWith('AI Support is now assisting')) {
            isEvent = true;
        }

        if (msg && msg.direction === 'OUTBOUND' && (msg.sender_type === 'EMPLOYEE' || msg.sender_type === 'SYSTEM' || isEvent)) {
          addMessage(msg.message_text, 'bot', msg.sender_name, { url: msg.media_url, fileName: msg.file_name }, msg.sent_at, msg.sender_role, isEvent);
        }"""

content = content.replace(old_ws, new_ws)

# Update loadHistory call
old_hist = "d.messages.forEach(function (m) { addMessage(m.text, m.from, m.sender_name, { url: m.media_url, fileName: m.file_name }, m.sent_at); });"
new_hist = "d.messages.forEach(function (m) { addMessage(m.text, m.from, m.sender_name, { url: m.media_url, fileName: m.file_name }, m.sent_at, m.sender_role, m.is_conversation_event); });"

content = content.replace(old_hist, new_hist)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('Success')
