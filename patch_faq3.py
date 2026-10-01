import os
import re

files_to_patch = [
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\widgets_edit.blade.php',
    r'c:\xampp\htdocs\chatbot\resources\views\widgets\edit.blade.php'
]

pattern_html = r'<label class="form-label"><i data-lucide="paperclip" class="icon-sm text-muted me-1"></i>\s*Attachment</label>\s*<input type="file" class="form-control" name="attachment">'

replacement_html = '''<label class="form-label"><i data-lucide="paperclip" class="icon-sm text-muted me-1"></i> Attachment</label>
              <div class="input-group">
                <input type="file" class="form-control" name="attachment" id="faqAttachmentInputAdd">
                <button class="btn btn-outline-secondary" type="button" id="btnClearAttachmentInputAdd" title="Clear selected file" style="display: none; padding: 0.375rem 0.75rem;">
                  <i data-lucide="x" class="icon-sm"></i>
                </button>
              </div>'''

js_to_append = '''
          // Clear attachment on add form
          const attachInputAdd = document.getElementById('faqAttachmentInputAdd');
          const btnClearAttachAdd = document.getElementById('btnClearAttachmentInputAdd');
          if (attachInputAdd && btnClearAttachAdd) {
              attachInputAdd.addEventListener('change', function() {
                  btnClearAttachAdd.style.display = this.value ? 'block' : 'none';
              });
              btnClearAttachAdd.addEventListener('click', function() {
                  attachInputAdd.value = '';
                  btnClearAttachAdd.style.display = 'none';
              });
          }
'''

for fpath in files_to_patch:
    if os.path.exists(fpath):
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        new_content = re.sub(pattern_html, replacement_html, content)
        
        # Now append the JS if it's not already there
        if js_to_append.strip() not in new_content:
            # Insert before </script>\n@endpush
            new_content = new_content.replace('      });\n  </script>\n@endpush', '      });\n' + js_to_append + '  </script>\n@endpush')
        
        if new_content != content:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f'Patched {fpath}')
        else:
            print(f'No changes made to {fpath}')
