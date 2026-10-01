import os

files_to_patch = [
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\widgets_edit.blade.php',
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\edit.blade.php'
]

for fpath in files_to_patch:
    if os.path.exists(fpath):
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        new_content = content.replace(
            '<p class="text-muted small mb-2">Paste this before <code>&lt;/body&gt;</code> on any page:</p>',
            '<p class="fw-bold small mb-2">Paste this before <code>&lt;/body&gt;</code> on any page:</p>'
        )
        
        if new_content != content:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f'Patched {fpath}')
        else:
            print(f'No changes made to {fpath}')
