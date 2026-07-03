package com.whatsapp.app.controlleer;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/webhook")
public class WebhookController {

    // 1. Meta Webhook Verification (GET Handler)
    @GetMapping
    public ResponseEntity<String> verify(
            @RequestParam("hub.mode") String mode,
            @RequestParam("hub.verify_token") String token,
            @RequestParam("hub.challenge") String challenge) {
        
        // Replace "YOUR_VERIFY_TOKEN" with the exact string you configured in the Meta Dashboard
        if ("YOUR_VERIFY_TOKEN".equals(token)) {
            return ResponseEntity.ok(challenge);
        }
        
        return ResponseEntity.status(HttpStatus.FORBIDDEN).body("Verification failed");
    }

    // 2. Incoming Messages Ingestion (POST Handler)
    @PostMapping
    public ResponseEntity<String> receive(@RequestBody String payload) {
        System.out.println("Received Meta Webhook Payload: " + payload);
        
        // Always return 200 OK or EVENT_RECEIVED quickly to acknowledge receipt to Meta
        return ResponseEntity.ok("EVENT_RECEIVED");
    }
}
