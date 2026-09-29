import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Update the websocket NewMessage listener
old_ws = "        if (msg.is_conversation_event) {"
new_ws = "        var txt = msg.message_text || '';\n        if (txt.endsWith('joined conversation') || txt.startsWith('ended conversation') || txt.startsWith('AI Support is now assisting')) {\n            msg.is_conversation_event = true;\n        }\n        if (msg.is_conversation_event) {"

content = content.replace(old_ws, new_ws)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('Success')
