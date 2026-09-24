import re

file_path = r'c:\xampp\htdocs\chatbot\resources\views\conversations\index.blade.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = """                <select name="assigned_to" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                  <option value="">Assigned To</option>
                  @foreach($allUsers as $user)
                      <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                  @endforeach
                </select>"""

replacement = """                <select name="assigned_to" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                  <option value="">Assigned To</option>
                  @foreach($allEmployees as $employee)
                      <option value="{{ $employee->id }}" {{ request('assigned_to') == $employee->id ? 'selected' : '' }}>{{ $employee->display_name ?? ($employee->first_name . ' ' . $employee->last_name) }}</option>
                  @endforeach
                </select>"""

# Normalize line endings just in case
content = content.replace('\r\n', '\n')
target = target.replace('\r\n', '\n')

content = content.replace(target, replacement)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
