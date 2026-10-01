import os

fpath = r'c:\xampp\htdocs\chatbot\resources\views\contacts\show.blade.php'

if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # First instance in timeline
    content = content.replace(
        "CONV-{{ str_pad($conv->id, 5, '0', STR_PAD_LEFT) }}",
        "{{ $conv->formatted_id }}"
    )
    
    # Second instance in the table
    # It has a '#' prefix in the template: <td class="fw-bold text-dark">#CONV-{{ str_pad($conv->id, 5, '0', STR_PAD_LEFT) }}</td>
    # But since we replaced the string above globally, it might have become: <td class="fw-bold text-dark">#{{ $conv->formatted_id }}</td>
    # Let's ensure it has '#' prefix. Wait, if $conv->formatted_id returns "CONV-00021", then "#{{ $conv->formatted_id }}" yields "#CONV-00021" which is exactly what it was doing.

    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Patched {fpath}')
else:
    print('File not found')
