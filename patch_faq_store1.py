import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\faq_store.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Add tenant_id to Source
content = re.sub(r'(class Source:.*?keywords:\s+List\[str\]\s+=\s+field\(default_factory=list\))', r'\1\n    tenant_id: Optional[int] = None', content, flags=re.DOTALL)

# Add tenant_id to Chunk
content = re.sub(r'(class Chunk:.*?keywords:\s+List\[str\]\s+=\s+field\(default_factory=list\))', r'\1\n    tenant_id: Optional[int] = None', content, flags=re.DOTALL)

# Add tenant_id to add_source
content = re.sub(r'(def add_source\(.*?keywords:\s+Optional\[List\[str\]\]\s+=\s+None,)', r'\1\n        tenant_id: Optional[int] = None,', content, flags=re.DOTALL)

content = re.sub(r'(source_url=source_url,\n\s+send_as_link=send_as_link,\n\s+keywords=keywords_list,\n\s+\))', r'\1\n            tenant_id=tenant_id,\n        ', content)

content = re.sub(r'(source_url=source_url,\n\s+send_as_link=send_as_link,\n\s+keywords=keywords_list,\n\s+\))', r'\1\n            tenant_id=tenant_id,\n        ', content) # for Chunk creation. Note: using regex replacement like this is risky. Let's do it carefully.

