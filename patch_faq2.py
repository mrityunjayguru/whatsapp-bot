import os
import re

files_to_patch = [
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\widgets_faq-edit.blade.php',
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\widgets_edit.blade.php',
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\faq-edit.blade.php',
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\edit.blade.php'
]

pattern = r'<div class="mb-3">\s*<div class="form-check form-switch">\s*<input type="checkbox" class="form-check-input" name="is_active" [^>]+>\s*<label class="form-check-label" [^>]+>Active</label>\s*</div>\s*</div>'

for fpath in files_to_patch:
    if os.path.exists(fpath):
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        new_content = re.sub(pattern, '', content, flags=re.DOTALL)
        
        if new_content != content:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f'Patched {fpath}')
        else:
            print(f'No changes made to {fpath}')
