import os
import re

files_to_patch = [
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\widgets_edit.blade.php',
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\edit.blade.php'
]

pattern = r'<div class="mb-3">\s*<label class="form-label">Client contact email</label>'
replacement = '''<div class="mb-3">
              <label class="form-label">Company email</label>
              <input type="text" class="form-control bg-light text-muted" value="{{ $company ? $company->contact_email : \'\' }}" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label">Client contact email</label>'''

for fpath in files_to_patch:
    if os.path.exists(fpath):
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        new_content = re.sub(pattern, replacement, content)
        
        if new_content != content:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f'Patched {fpath}')
        else:
            print(f'No changes made to {fpath}')
