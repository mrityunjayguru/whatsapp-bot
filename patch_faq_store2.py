import os

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\faq_store.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    'send_as_link: bool = False\n    keywords: List[str] = field(default_factory=list)',
    'send_as_link: bool = False\n    keywords: List[str] = field(default_factory=list)\n    tenant_id: Optional[int] = None'
)

content = content.replace(
    'keywords: Optional[List[str]] = None,\n    ) -> Source:',
    'keywords: Optional[List[str]] = None,\n        tenant_id: Optional[int] = None,\n    ) -> Source:'
)

content = content.replace(
    'keywords=keywords_list,\n        )',
    'keywords=keywords_list,\n            tenant_id=tenant_id,\n        )'
)

content = content.replace(
    'def search(self, query: str, top_k: int = 3) -> List[dict]:',
    'def search(self, query: str, top_k: int = 3, tenant_id: Optional[int] = None) -> List[dict]:'
)

content = content.replace(
    'corpus_chunks = self.chunks',
    'corpus_chunks = [c for c in self.chunks if c.tenant_id == tenant_id] if tenant_id is not None else self.chunks'
)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('faq_store.py patched!')
