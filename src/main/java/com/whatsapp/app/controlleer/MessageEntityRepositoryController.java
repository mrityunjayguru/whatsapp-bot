package com.whatsapp.app.controlleer;



import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;

import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.whatsapp.app.Repository.MessageEntityRepository;
import com.whatsapp.app.model.MessageEntity;


@RestController
@RequestMapping("/api/messages")
public class MessageEntityRepositoryController {

    @Autowired
    MessageEntityRepository messageEntityRepository;
    
    @GetMapping("/allmessage")
    public ResponseEntity<List<MessageEntity>> allMessage() {
        return ResponseEntity.ok(messageEntityRepository.findAll());
    }

}
