import os
import re

blade_file = r'c:\xampp\htdocs\chatbot\resources\views\auth\register-company.blade.php'
if os.path.exists(blade_file):
    with open(blade_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Add red * to Company Name
    content = content.replace('<label for="company_name" class="form-label">Company Name</label>', '<label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>')
    
    # 2. Add red * to Email address
    content = content.replace('<label for="email" class="form-label">Email address</label>', '<label for="email" class="form-label">Email address <span class="text-danger">*</span></label>')
    
    # 3. Remove required from contact number input
    content = re.sub(r'(<input type="text" class="form-control" name="contact_number".*?) required>', r'\1>', content)
    
    with open(blade_file, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Blade updated")

ctrl_file = r'c:\xampp\htdocs\chatbot\app\Http\Controllers\Auth\CompanyRegisteredUserController.php'
if os.path.exists(ctrl_file):
    with open(ctrl_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Change contact_number to nullable
    content = content.replace("'contact_number' => ['required', 'string', 'max:20'],", "'contact_number' => ['nullable', 'string', 'max:20'],")
    
    with open(ctrl_file, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Controller updated")
