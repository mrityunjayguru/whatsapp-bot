import os

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\widget_routes.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = '''      if (attachment && attachment.url) {
        var att = document.createElement('a');
        att.className = 'ws-bub ws-attachment';
        att.href = attachment.url;
        att.target = '_blank';
        att.rel = 'noopener noreferrer';'''

replacement = '''      if (attachment && attachment.url) {
        var att = document.createElement('a');
        att.className = 'ws-bub ws-attachment';
        att.href = attachment.url;
        att.target = '_blank';
        att.setAttribute('download', attachment.fileName || 'download');
        att.rel = 'noopener noreferrer';'''

if target in content:
    content = content.replace(target, replacement)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print('Patched successfully!')
else:
    print('Target string not found!')
