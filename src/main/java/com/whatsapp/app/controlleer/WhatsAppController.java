package com.whatsapp.app.controlleer;

import com.whatsapp.app.Repository.ContactEntityRepository;
import com.whatsapp.app.Repository.ConversationEntityRepository;
import com.whatsapp.app.Repository.WebhookRepository;
import com.whatsapp.app.model.ContactEntity;
import com.whatsapp.app.model.ConversationEntity;
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

           System.out.println(" to "+to);
           System.out.println(" message "+message);

           

          ContactEntity contactEntity = contactEntityRepository.findBPhonenumber(to);
          
          System.out.println("=====================contactEntity===============");
          System.out.println(contactEntity);
          System.out.println(contactEntity);
          System.out.println("=====================contactEntity===============");

        ConversationEntity conversationEntity = new ConversationEntity();
        conversationEntity.setTenant_id(contactEntity.getTenantid());
        conversationEntity.setWhatsapp_phone_number_id(contactEntity.getWhatsappphonenumberid());
        conversationEntity.setPhonenumber(contactEntity.getPhonenumber());
        conversationEntity.setProfilename(contactEntity.getWhatsappprofilename());
        conversationEntity.setMessagestatus("Sended");
        conversationEntity.setContact_id(contactEntity.getId());
        conversationEntity.setTitle("Default Title");
        conversationEntity.setStatus("Open");
        conversationEntity.setMessageBody(message);


        conversationEntityRepository.save(conversationEntity);


        return service.sendMessage(to, message);
    }


}