import os
import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

pattern = r"(att\.href = attachment\.url;\s*att\.target = '_blank';\s*att\.rel = 'noopener noreferrer';)"
replacement = r"att.href = attachment.url;\n        att.target = '_blank';\n        att.setAttribute('download', attachment.fileName || 'download');\n        att.rel = 'noopener noreferrer';"

if re.search(pattern, content):
    content = re.sub(pattern, replacement, content)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print('Patched successfully!')
else:
    print('Target string not found!')
