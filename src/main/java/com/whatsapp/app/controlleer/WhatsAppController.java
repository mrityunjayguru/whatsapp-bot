package com.whatsapp.app.controlleer;

import com.whatsapp.app.Repository.ContactEntityRepository;
import com.whatsapp.app.Repository.ConversationEntityRepository;
import com.whatsapp.app.Repository.MessageEntityRepository;
import com.whatsapp.app.Repository.WebhookRepository;
import com.whatsapp.app.model.ContactEntity;
import com.whatsapp.app.model.ConversationEntity;
import com.whatsapp.app.model.MessageEntity;
import com.whatsapp.app.services.TwilioService;
import com.whatsapp.app.services.WhatsAppService;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/whatsapp")
public class WhatsAppController {

    @Autowired
    private TwilioService twilioService;

        @Autowired
    private WebhookRepository webhookRepository;

    @Autowired
    private ContactEntityRepository contactEntityRepository;

    @Autowired 
    private ConversationEntityRepository conversationEntityRepository;

    @Autowired
    MessageEntityRepository messageEntityRepository;


    @PostMapping("/send")
    public String sendWhatsAppMessage(@RequestParam("to") String to, 
                                      @RequestParam("message") String message) {


        System.out.println(" to "+to);
        System.out.println(" message "+message);


        return twilioService.sendWhatsAppMessage(to, message);
    }


    @Autowired
    private WhatsAppService service;

@PostMapping("/send1")
public String send(@RequestParam("to") String to,
                   @RequestParam("message") String message) {

    System.out.println("To : " + to);
    System.out.println("Message : " + message);
                    System.out.println("To : " + to);
    System.out.println("Message : " + message);

    System.out.println("To : " + to);
    System.out.println("Message : " + message);

    System.out.println("To : " + to);
    System.out.println("Message : " + message);


    // Find existing contact
    ContactEntity contactEntity = contactEntityRepository.findByPhonenumber(to);

    System.out.println("contactEntity");
    System.out.println("contactEntity");
    System.out.println("contactEntity");

    System.out.println(contactEntity);


    System.out.println("contactEntity");
    System.out.println("contactEntity");
    System.out.println("contactEntity");


    // Create contact if it does not exist
    if (contactEntity == null) {
        contactEntity = new ContactEntity();

        contactEntity.setPhonenumber(to);
        contactEntity.setMessageBody(message);
        contactEntity.setTenantid(contactEntityRepository.getNextTenantId());
        contactEntity.setWhatsappphonenumberid(contactEntityRepository.getNextWhatsappphonenumberId());
       
        contactEntity.setHumanboatsetting("ENABLED_BOAT");


        contactEntity = contactEntityRepository.save(contactEntity);
        
    System.out.println("contactEntity");
    System.out.println("contactEntity");
    System.out.println("contactEntity");

    System.out.println(contactEntity);


    System.out.println("contactEntity");
    System.out.println("contactEntity");
    System.out.println("contactEntity");
    }

    System.out.println("========== Contact ==========");
    System.out.println(contactEntity);
    System.out.println("=============================");

    // Create Conversation
    ConversationEntity conversationEntity = new ConversationEntity();
    conversationEntity.setTenant_id(contactEntity.getTenantid());
    conversationEntity.setWhatsapp_phone_number_id(contactEntity.getWhatsappphonenumberid());
    conversationEntity.setPhonenumber(contactEntity.getPhonenumber());
    conversationEntity.setProfilename(contactEntity.getWhatsappprofilename());
    conversationEntity.setMessagestatus("Sent");   // Fixed spelling
    conversationEntity.setContact_id(contactEntity.getId());
    conversationEntity.setTitle("Default Title");
    conversationEntity.setStatus("Open");
    conversationEntity.setMessagebody(message);

    ConversationEntity savedConversation =
            conversationEntityRepository.save(conversationEntity);

    // Create Message
    MessageEntity messageEntity = new MessageEntity();
    messageEntity.setMessageid(messageEntityRepository.getNextMessageId());
    messageEntity.setTenantid(contactEntity.getTenantid());
    messageEntity.setWhatsappphonenumberid(contactEntity.getWhatsappphonenumberid());
    messageEntity.setContactid(contactEntity.getId());
    messageEntity.setPhonenumber(contactEntity.getPhonenumber());
    messageEntity.setProfilename(contactEntity.getWhatsappprofilename());
    messageEntity.setDirection("Outbound");
    messageEntity.setMessagetext(message);
    messageEntity.setMessagebody(message);
    messageEntity.setConversationentityid(savedConversation.getId());

    messageEntityRepository.save(messageEntity);

    return service.sendMessage(to, message);
}

}