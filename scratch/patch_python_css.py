import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Update CSS
old_css = "'.ws-row.ws-user .ws-bub{background:' + C + ';color:#fff;border-bottom-right-radius:4px}',"
new_css = "'.ws-row.ws-user .ws-bub{background:' + C + ';color:#fff;border-bottom-right-radius:4px}',\n    '.ws-row.ws-event{justify-content:center;margin:8px 0}',\n    '.ws-event-bub{background:#e2e8f0;color:#475569;font-size:11px;font-weight:600;padding:4px 12px;border-radius:12px;text-align:center}',"

content = content.replace(old_css, new_css)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('Success CSS')
