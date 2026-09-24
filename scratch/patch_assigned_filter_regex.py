import re

file_path = r'c:\xampp\htdocs\chatbot\resources\views\conversations\index.blade.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace $allUsers with $allEmployees
content = re.sub(
    r'@foreach\(\$allUsers as \$user\).*?@endforeach',
    r'''@foreach($allEmployees as $employee)
                      <option value="{{ $employee->id }}" {{ request('assigned_to') == $employee->id ? 'selected' : '' }}>{{ $employee->display_name ?? ($employee->first_name . ' ' . $employee->last_name) }}</option>
                  @endforeach''',
    content,
    flags=re.DOTALL
)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
