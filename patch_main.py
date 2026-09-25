import os

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\main.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    'faq_store.add_source(\n            name=payload.name,\n            source_type="text",\n            text=payload.text,\n            source_url=payload.source_url,\n            send_as_link=payload.send_as_link,\n            keywords=payload.keywords,\n        )',
    'faq_store.add_source(\n            name=payload.name,\n            source_type="text",\n            text=payload.text,\n            source_url=payload.source_url,\n            send_as_link=payload.send_as_link,\n            keywords=payload.keywords,\n            tenant_id=payload.tenant_id,\n        )'
)

content = content.replace(
    'return generate_reply(payload.message)',
    'return generate_reply(payload.message, tenant_id=payload.tenant_id)'
)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('main.py patched!')
