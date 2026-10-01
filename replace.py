import re

file_path = r'C:\Users\HP\Downloads\widget_routes.py'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace is_active=payload.is_active, with nothing
old_str = "is_active=payload.is_active,"

if old_str in content:
    content = content.replace(old_str, "")
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Successfully removed is_active=payload.is_active, from widget_routes.py")
else:
    print("Could not find is_active=payload.is_active, in widget_routes.py")

