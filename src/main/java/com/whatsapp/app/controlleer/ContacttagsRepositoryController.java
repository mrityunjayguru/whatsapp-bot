package com.whatsapp.app.controlleer;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.whatsapp.app.Repository.ContacttagsRepository;
import com.whatsapp.app.model.Contacttags;

@RestController
@RequestMapping("/api/contacttags")

public class ContacttagsRepositoryController {


    @Autowired
    private ContacttagsRepository contacttagsRepository;

    @PostMapping
    public ResponseEntity<Contacttags> createTag(@RequestBody Contacttags contacttags) {
        Contacttags savedContacttags = contacttagsRepository.save(contacttags);
        return ResponseEntity.status(HttpStatus.CREATED).body(savedContacttags);
    }


    @GetMapping
    public ResponseEntity<Iterable<Contacttags>> allTag() {
        Iterable<Contacttags> contacttags = contacttagsRepository.findAll();
        return ResponseEntity.ok(contacttags);
    }
}