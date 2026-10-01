import os

fpath = r'c:\xampp\htdocs\chatbot\resources\views\conversations\show.blade.php'

if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # PHP rendering
    content = content.replace(
        '<span class="badge bg-light text-muted border px-3 py-2 rounded-pill">{!! trp_render_chat_text($msg) !!}</span>',
        '<span class="badge bg-light text-muted border px-3 py-2 rounded-pill">{!! ucfirst(trp_render_chat_text($msg)) !!}</span>'
    )
    
    # JS rendering
    old_js = "bubble.innerHTML = `<span class=\"badge bg-light text-muted border px-3 py-2 rounded-pill\">${msg.message_text}</span>`;"
    new_js = """let displayText = msg.message_text || '';
            if (displayText.toLowerCase().startsWith('ended conversation')) {
                displayText = displayText.charAt(0).toUpperCase() + displayText.slice(1);
            }
            bubble.innerHTML = `<span class="badge bg-light text-muted border px-3 py-2 rounded-pill">${displayText}</span>`;"""
    
    content = content.replace(old_js, new_js)

    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Patched {fpath}')
else:
    print('File not found')
