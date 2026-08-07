package com.whatsapp.app.controlleer;

import java.util.Arrays;
import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.CrossOrigin;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.whatsapp.app.Repository.ConversationEntityRepository;
import com.whatsapp.app.model.ConversationEntity;


@RestController
@RequestMapping("/api/conversation")

public class ConversationEntityRepositoryController {

    @Autowired
    private ConversationEntityRepository conversationEntityRepository;

    @PostMapping
    public ResponseEntity<?> createTag(@RequestBody ConversationEntity conversationEntity) {

        //tags.setTagid(tagsRepository.getNextTagId());
        ConversationEntity savedConversationEntity = conversationEntityRepository.save(conversationEntity);
          return ResponseEntity
            .status(HttpStatus.CREATED)
            .body("Data saved successfully");
    }


    @GetMapping
    public ResponseEntity<Iterable<ConversationEntity>> allTag() {
        Iterable<ConversationEntity> conversationEntities = conversationEntityRepository.findAll();
      
      System.out.println(" Niraj");
      System.out.println(" Niraj");
      System.out.println(" Niraj");
      System.out.println(" Niraj");
      System.out.println(" Niraj");
      System.out.println(" Niraj");
      System.out.println(" Niraj");
      System.out.println(" Niraj");

        return ResponseEntity.ok(conversationEntities);
    }


    @GetMapping("/byuniquephonenumber")
    public ResponseEntity<List<ConversationEntity>> uniqueConversationByPhonenumber() {
        
                return ResponseEntity.ok(conversationEntityRepository.findLatestConversationPerPhoneNumber());
    }



     @GetMapping("/byphonenumber/{phonenumber}")
    public ResponseEntity<?> getTagByTagId(@PathVariable String phonenumber) {
            
            List<ConversationEntity> conversationEntities = conversationEntityRepository.findByPhonenumber(phonenumber);
                    if (conversationEntities == null) {
                        return ResponseEntity.notFound().build();
                    }
                    return ResponseEntity.ok(conversationEntities);
                }


                
     @GetMapping("/byphonenumbergetlastsentopen/{phonenumber}")
    public ResponseEntity<?> getLastSentOpen(@PathVariable String phonenumber) {
            
            List<ConversationEntity> conversationEntities = conversationEntityRepository.findConversationAfterLastSentOpen(phonenumber);
                    if (conversationEntities == null) {
                        return ResponseEntity.notFound().build();
                    }
                    return ResponseEntity.ok(conversationEntities);
                }
 


}
