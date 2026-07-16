package com.whatsapp.app.controlleer;


import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import com.whatsapp.app.Repository.WebhookRepository;
import com.whatsapp.app.model.WebhookEvent;



@RestController
@RequestMapping("/allwebhooks")
public class AllWebhookController {

    
    @Value("${webhook.verify.token}")
    private String webhookVerifyToken;

    @Autowired
    private WebhookRepository webhookRepository;

    


    @GetMapping
    public ResponseEntity<List<WebhookEvent>> getAllWebHookData() {
        List<WebhookEvent> webhookEvents = webhookRepository.findAll();
        return ResponseEntity.ok(webhookEvents);
    }
}
