import re

file_path = r'c:\xampp\htdocs\chatbot\resources\views\widgets\edit.blade.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace $widget['valid_from'] with $company->valid_from
content = content.replace("$widget['valid_from'] ?? ''", "$company->valid_from ?? ''")
content = content.replace("$widget['expiry_date'] ?? ''", "$company->expiry_date ?? ''")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
