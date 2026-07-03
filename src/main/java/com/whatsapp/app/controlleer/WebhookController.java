package com.whatsapp.app.controlleer;


import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;


@RestController
@RequestMapping("/webhook")
public class WebhookController {

    @GetMapping
    public String verify(
            @RequestParam("hub.mode") String mode,
            @RequestParam("hub.verify_token") String token,
            @RequestParam("hub.challenge") String challenge) {

        if ("YOUR_VERIFY_TOKEN".equals(token)) {
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
