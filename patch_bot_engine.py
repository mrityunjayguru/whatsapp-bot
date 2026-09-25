import os

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\bot_engine.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    '    history: Optional[List[HistoryItem]] = None,\n) -> ReplyResponse:',
    '    history: Optional[List[HistoryItem]] = None,\n    tenant_id: Optional[int] = None,\n) -> ReplyResponse:'
)

content = content.replace(
    'results = faq_store.search(message, top_k=3)',
    'results = faq_store.search(message, top_k=3, tenant_id=tenant_id)'
)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('bot_engine.py patched!')
