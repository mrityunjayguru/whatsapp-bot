package com.whatsapp.app.controlleer;


import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;


@RestController
@RequestMapping("/webhook")
public class WebhookController {

    
    @Value("${webhook.verify-token}")
    private String webhookVerifyToken;

    @GetMapping
    public String verify(
            @RequestParam("hub.mode") String mode,
            @RequestParam("hub.verify_token") String token,
            @RequestParam("hub.challenge") String challenge) {

                

        if (this.webhookVerifyToken.equals(token)) {
            return challenge;
        }

        return "Verification failed";
    }

    @PostMapping
    public ResponseEntity<String> receive(@RequestBody String payload) {
        System.out.println(payload);
        return ResponseEntity.ok("EVENT_RECEIVED");
    }
}
