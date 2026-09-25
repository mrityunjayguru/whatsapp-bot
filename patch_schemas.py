import re

file_path = r'C:\Users\HP\Downloads\whatsapp-bot-api\whatsapp-bot-api\schemas.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = re.sub(r'(class ReplyRequest\(BaseModel\):.*?\n\s+message:\s+str\s+=\s+Field\(\n\s+...,\s+description="The inbound message text from the customer"\n\s+\))', r'\1\n    tenant_id: Optional[int] = Field(None, description="Company tenant ID")', content, flags=re.DOTALL)

content = re.sub(r'(class FAQTextRequest\(BaseModel\):.*?send_as_link:\s+Optional\[bool\]\s+=\s+Field\([^)]+\))', r'\1\n    tenant_id: Optional[int] = Field(None, description="Company tenant ID")', content, flags=re.DOTALL)

content = re.sub(r'(class FAQSourceResponse\(BaseModel\):.*?send_as_link:\s+bool\s+=\s+False)', r'\1\n    tenant_id: Optional[int] = None', content, flags=re.DOTALL)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('schemas.py patched!')
