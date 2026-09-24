import re

file_path = r'c:\xampp\htdocs\chatbot\app\Http\Controllers\WidgetMessageController.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Add a checkCompany method
check_company_method = """    private function checkCompany(string $token)
    {
        $now = now();
        return \App\Models\Company::where('widget_token', $token)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $now);
            })
            ->first();
    }
"""

if "private function checkCompany" not in content:
    content = content.replace("private function findConversation", check_company_method + "\n    private function findConversation")

# Update send() to use checkCompany
send_target = """        $company = Company::where('widget_token', $validated['token'])
            ->where('is_active', true)
            ->first();"""
send_replacement = """        $company = $this->checkCompany($validated['token']);"""
content = content.replace(send_target, send_replacement)

# Update history() to use checkCompany
history_target = """        $conversation = $this->findConversation($validated['token'], $validated['session_id']);"""
history_replacement = """        if (!$this->checkCompany($validated['token'])) {
            return response()->json(['error' => 'Unknown or inactive widget.'], 404);
        }

        $conversation = $this->findConversation($validated['token'], $validated['session_id']);"""
content = content.replace(history_target, history_replacement)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("SUCCESS")
