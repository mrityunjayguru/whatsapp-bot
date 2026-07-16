package com.whatsapp.app.controlleer;


import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import com.fasterxml.jackson.databind.JsonMappingException;
import com.fasterxml.jackson.databind.JsonNode;
import com.whatsapp.app.Repository.ContactEntityRepository;
import com.whatsapp.app.Repository.WebhookRepository;
import com.whatsapp.app.model.ContactEntity;
import com.whatsapp.app.model.Contacttags;
import com.whatsapp.app.model.WebhookEvent;
import com.fasterxml.jackson.core.JsonProcessingException;

import com.fasterxml.jackson.databind.ObjectMapper;


@RestController
@RequestMapping("/webhook")
public class WebhookController {

    
    @Value("${webhook.verify.token}")
    private String webhookVerifyToken;

    @Autowired
    private WebhookRepository webhookRepository;

    @Autowired
    private ContactEntityRepository contactEntityRepository;


    
    @GetMapping
    public ResponseEntity<String> verifyWebhook(
            @RequestParam("hub.mode") String mode,
            @RequestParam("hub.verify_token") String token,
            @RequestParam("hub.challenge") String challenge) {


                System.out.println(" =========================");
                System.out.println(webhookVerifyToken);

        if ("subscribe".equals(mode) && webhookVerifyToken.equals(token)) {
            return ResponseEntity.ok(challenge);
        }

        return ResponseEntity.status(HttpStatus.FORBIDDEN).build();
    }

    @PostMapping
    public ResponseEntity<String> receiveWebhook(@RequestBody String payload) throws JsonMappingException, JsonProcessingException {

        System.out.println("Webhook Received:");
        System.out.println(payload);

         WebhookEvent entity = new WebhookEvent();
         entity.setPayload(payload);
         webhookRepository.save(entity);

        ObjectMapper mapper = new ObjectMapper();
        JsonNode root = mapper.readTree(payload);

        String phoneNumberId = root.get("entry").get(0).get("changes").get(0).get("value").get("contacts").get(0).get("wa_id").asText();
        String profileName = root.get("entry").get(0).get("changes").get(0).get("value").get("contacts").get(0).get("profile").get("name").asText();
        if (!contactEntityRepository.existsByphonenumber(phoneNumberId)) {
                    ContactEntity  contactEntity = new ContactEntity();
                        contactEntity.setPayload(payload);
                        contactEntity.setTenantid(contactEntityRepository.getNextTenantId());
                        contactEntity.setWhatsappphonenumberid(contactEntityRepository.getNextWhatsappphonenumberId());
                        contactEntity.setPhonenumber(phoneNumberId);
                        contactEntity.setWhatsappprofilename(profileName);
                        contactEntityRepository.save(contactEntity);
        }
        else{
            System.out.println(" Phone number must be unique");
        }

        return ResponseEntity.ok("EVENT_RECEIVED");
    }


    @GetMapping("/allwebhooks")
    public ResponseEntity<List<WebhookEvent>> getAllWebHookData() {
        List<WebhookEvent> webhookEvents = webhookRepository.findAll();
        return ResponseEntity.ok(webhookEvents);
    }



    // for update 
/* 

    @PostMapping("/update")
    public ResponseEntity<ContactEntity> updateTag(@RequestBody ContactEntity contactEntity) {

        if (!contactEntityRepository.existsById(contactEntity.getId())) {
            return ResponseEntity.notFound().build();
        }

        ContactEntity  updatedContactEntity = contactEntityRepository.save(contactEntity);

        return ResponseEntity.ok(updatedContactEntity);
    }*/

@PostMapping("/update")
public ResponseEntity<?> updateContact(
        @RequestBody ContactEntity contactEntity) {

    Optional<ContactEntity> optional = contactEntityRepository.findById(contactEntity.getId());
if (optional.isEmpty()) {
    return ResponseEntity
            .status(HttpStatus.NOT_FOUND)
            .body("Contact not found with id: " + contactEntity.getId());
}

    ContactEntity existing = optional.get();

    if (contactEntity.getCustomname() != null) {
        existing.setCustomname(contactEntity.getCustomname());
    }

    if (contactEntity.getEmail() != null) {
        existing.setEmail(contactEntity.getEmail());
    }

    if (contactEntity.getPayload() != null) {
        existing.setPayload(contactEntity.getPayload());
    }

    if (contactEntity.getPhonenumber() != null) {
        existing.setPhonenumber(contactEntity.getPhonenumber());
    }

    if (contactEntity.getTags() != null) {
        existing.setTags(contactEntity.getTags());
    }

    if (contactEntity.getTenantid() != null) {
        existing.setTenantid(contactEntity.getTenantid());
    }

    if (contactEntity.getWhatsappphonenumberid() != null) {
        existing.setWhatsappphonenumberid(contactEntity.getWhatsappphonenumberid());
    }

    if (contactEntity.getWhatsappprofilename() != null) {
        existing.setWhatsappprofilename(contactEntity.getWhatsappprofilename());
    }

    // Optional: update timestamp
    existing.setUpdatedat(LocalDateTime.now());

    ContactEntity updated = contactEntityRepository.save(existing);

    return ResponseEntity.ok(updated);
}

        
}
