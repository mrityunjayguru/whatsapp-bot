import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = "if (msg && msg.direction === 'OUTBOUND' && msg.sender_type === 'EMPLOYEE') {"
replacement = "if (msg && msg.direction === 'OUTBOUND' && (msg.sender_type === 'EMPLOYEE' || msg.sender_type === 'SYSTEM')) {"

content = content.replace(target, replacement)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
