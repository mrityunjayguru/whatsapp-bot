import os

fpath = r'c:\xampp\htdocs\chatbot\resources\views\contacts\show.blade.php'

if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Hide ACTION column header
    old_th = '<th class="pt-2 pb-2 text-center">ACTION</th>'
    new_th = """@if($isCompanyAdmin || auth()->id() === 1)
                  <th class="pt-2 pb-2 text-center">ACTION</th>
                  @endif"""
    content = content.replace(old_th, new_th)

    # Hide ACTION column cell
    old_td = """                    <td class="text-center">
                      <a href="{{ route('conversations.show', $conv->id) }}" class="btn btn-sm btn-light btn-icon border" title="View Conversation">
                        <i data-lucide="eye" class="icon-sm"></i>
                      </a>
                    </td>"""
    new_td = """                    @if($isCompanyAdmin || auth()->id() === 1)
                    <td class="text-center">
                      <a href="{{ route('conversations.show', $conv->id) }}" class="btn btn-sm btn-light btn-icon border" title="View Conversation">
                        <i data-lucide="eye" class="icon-sm"></i>
                      </a>
                    </td>
                    @endif"""
    content = content.replace(old_td, new_td)
    
    # Also adjust colspan for empty state
    old_colspan = '<td colspan="3" class="text-center text-muted">No conversations found.</td>'
    new_colspan = '<td colspan="{{ ($isCompanyAdmin || auth()->id() === 1) ? 3 : 2 }}" class="text-center text-muted">No conversations found.</td>'
    content = content.replace(old_colspan, new_colspan)
    
    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched show.blade.php")
else:
    print("File not found")
