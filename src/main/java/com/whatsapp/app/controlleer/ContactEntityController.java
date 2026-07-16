package com.whatsapp.app.controlleer;


import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import com.whatsapp.app.Repository.ContactEntityRepository;

import com.whatsapp.app.model.ContactEntity;


@RestController
@RequestMapping("/allcontactentity")
public class ContactEntityController {

    @Value("${webhook.verify.token}")
    private String webhookVerifyToken;

    @Autowired
    private ContactEntityRepository contactEntityRepository;

    @GetMapping
    public ResponseEntity<List<ContactEntity>> getAllContactEntityData() {
        List<ContactEntity> contactEntities = contactEntityRepository.findAll();
        return ResponseEntity.ok(contactEntities);
    }

     @GetMapping("/by-whatsapp-phone/{whatsappphonenumberid}")
    public ResponseEntity<ContactEntity> getContactByWhatsappPhoneNumberId(
            @PathVariable Long whatsappphonenumberid) {

        ContactEntity contact = contactEntityRepository.findByWhatsappphonenumberid(whatsappphonenumberid);

        if (contact == null) {
            return ResponseEntity.notFound().build();
        }

        return ResponseEntity.ok(contact);
    }

}
