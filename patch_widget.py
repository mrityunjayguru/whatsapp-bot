import os

fpath = r'c:\xampp\htdocs\chatbot\app\Http\Controllers\WidgetMessageController.php'

if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Update findConversation signature and logic
    old_find = """    private function findConversation(string $token, string $sessionId): ?Conversation
    {
        $syntheticId = 'web:' . $token . ':' . $sessionId;
        $contact = Contact::where(function($q) use ($syntheticId) {
            $q->where('phone_number', $syntheticId)
              ->orWhere('whatsapp_profile_name', $syntheticId);
        })->first();
        if (!$contact) {
            return null;
        }
        return Conversation::where('contact_id', $contact->id)
            ->where('channel', 'web_widget')
            ->where('widget_token', $token)
            ->orderBy('id', 'desc')
            ->first();
    }"""

    new_find = """    private function findConversation(string $token, string $sessionId, ?string $ip = null): ?Conversation
    {
        $company = $this->checkCompany($token);
        if (!$company) return null;

        $syntheticId = 'web:' . $token . ':' . $sessionId;
        
        $contact = Contact::where('tenant_id', $company->id)
            ->where(function($q) use ($syntheticId, $ip) {
                if ($ip) {
                    $q->where('ip_address', $ip);
                } else {
                    $q->where('phone_number', $syntheticId)
                      ->orWhere('whatsapp_profile_name', $syntheticId);
                }
            })->first();
            
        if (!$contact && $ip) {
            $contact = Contact::where('tenant_id', $company->id)
                ->where(function($q) use ($syntheticId) {
                    $q->where('phone_number', $syntheticId)
                      ->orWhere('whatsapp_profile_name', $syntheticId);
                })->first();
        }

        if (!$contact) {
            return null;
        }
        return Conversation::where('contact_id', $contact->id)
            ->where('channel', 'web_widget')
            ->where('widget_token', $token)
            ->orderBy('id', 'desc')
            ->first();
    }"""
    content = content.replace(old_find, new_find)

    # 2. Update history method to pass IP
    content = content.replace(
        "$conversation = $this->findConversation($validated['token'], $validated['session_id']);",
        "$conversation = $this->findConversation($validated['token'], $validated['session_id'], $request->ip());"
    )

    # 3. Update send method to create contact using IP
    old_contact_create = """        $syntheticId = 'web:' . $validated['token'] . ':' . $validated['session_id'];
        
        $contact = Contact::where('tenant_id', $company->id)
            ->where(function ($query) use ($syntheticId) {
                $query->where('phone_number', $syntheticId)
                      ->orWhere('whatsapp_profile_name', $syntheticId);
            })->first();

        if (!$contact) {
            $contact = Contact::create([
                'phone_number' => $syntheticId,
                'whatsapp_profile_name' => $syntheticId,
                'tenant_id' => $company->id,
                'custom_name' => 'Website visitor',
            ]);
        }"""
        
    new_contact_create = """        $syntheticId = 'web:' . $validated['token'] . ':' . $validated['session_id'];
        $ip = $request->ip();
        
        $contact = Contact::where('tenant_id', $company->id)
            ->where(function ($query) use ($syntheticId, $ip) {
                if ($ip) {
                    $query->where('ip_address', $ip);
                } else {
                    $query->where('phone_number', $syntheticId)
                          ->orWhere('whatsapp_profile_name', $syntheticId);
                }
            })->first();
            
        if (!$contact && $ip) {
            $contact = Contact::where('tenant_id', $company->id)
                ->where(function ($query) use ($syntheticId) {
                    $query->where('phone_number', $syntheticId)
                          ->orWhere('whatsapp_profile_name', $syntheticId);
                })->first();
        }

        if (!$contact) {
            $contact = Contact::create([
                'phone_number' => $syntheticId,
                'whatsapp_profile_name' => $syntheticId,
                'tenant_id' => $company->id,
                'custom_name' => 'Website visitor',
                'ip_address' => $ip,
            ]);
        } elseif (!$contact->ip_address && $ip) {
            $contact->update(['ip_address' => $ip]);
        }"""
    content = content.replace(old_contact_create, new_contact_create)
    
    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched WidgetMessageController")
else:
    print("File not found")
