package com.whatsapp.app.controlleer;

import org.springframework.beans.factory.annotation.Autowired;
import com.whatsapp.app.Repository.ChatBoatEntityRepository;
import java.util.List;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import com.whatsapp.app.model.ChatBoatEntity;

@RestController
@RequestMapping("/chatboatentity")

public class ChatBoatEntityRepositoryController {    
    @Autowired
    private ChatBoatEntityRepository chatBoatEntityRepository;  

    @GetMapping
    public ResponseEntity<List<ChatBoatEntity>> getAllChatBoatEntity() {
        List<ChatBoatEntity> chatBoatEntity = chatBoatEntityRepository.findAll();
        return ResponseEntity.ok(chatBoatEntity);
    }


}
