package com.whatsapp.app.controlleer;

import com.whatsapp.app.services.WhatsAppService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/whatsapp")
public class WhatsAppController {

    @Autowired
    private WhatsAppService whatsAppService;

    @PostMapping("/send-template")
    public ResponseEntity<String> sendDynamicTemplate(
            @RequestParam String to,
            @RequestParam String templateName,
            @RequestParam List<String> params) {
        
        // Example: If template needs Name and Code, pass them comma-separated in Postman
        String result = whatsAppService.sendTemplateWithParams(to, templateName, params);
        return ResponseEntity.ok(result);
    }
}
