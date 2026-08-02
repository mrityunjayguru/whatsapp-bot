package com.whatsapp.app.controlleer;


import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import com.fasterxml.jackson.databind.JsonMappingException;
import com.fasterxml.jackson.databind.JsonNode;
import com.whatsapp.app.Repository.ChatBoatEntityRepository;
import com.whatsapp.app.Repository.ContactEntityRepository;
import com.whatsapp.app.Repository.ConversationEntityRepository;
import com.whatsapp.app.Repository.MessageEntityRepository;
import com.whatsapp.app.Repository.WebhookRepository;
import com.whatsapp.app.model.ChatBoatEntity;
import com.whatsapp.app.model.ContactEntity;
import com.whatsapp.app.model.Contacttags;
import com.whatsapp.app.model.ConversationEntity;
import com.whatsapp.app.model.MessageEntity;
import com.whatsapp.app.model.WebhookEvent;
import com.whatsapp.app.services.SendReplyToChatBaot;
import com.whatsapp.app.services.WhatsAppMediaDownloader;
import com.whatsapp.app.services.WhatsAppService;
import com.fasterxml.jackson.core.JsonProcessingException;

import com.fasterxml.jackson.databind.ObjectMapper;



@RestController
@RequestMapping("/webhook")
public class WebhookController {

     @Autowired
    private WhatsAppService servicesenddata;
    
    
    @Value("${webhook.verify.token}")
    private String webhookVerifyToken;

    
    @Value("${whatsapp.access-token}")
    private String whatsappaccesstoken ;

    
    @Value("${chatboaturl}")
    private String chatboaturl;

    @Autowired
    private ChatBoatEntityRepository chatBoatEntityRepository;  


    @Autowired
    private WebhookRepository webhookRepository;

    @Autowired
    private ContactEntityRepository contactEntityRepository;

    @Autowired 
    private ConversationEntityRepository conversationEntityRepository;

    @Autowired
    private MessageEntityRepository messageEntityRepository;


    
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

    /* 

    @PostMapping
    public ResponseEntity<String> receiveWebhook(@RequestBody String payload) throws JsonMappingException, JsonProcessingException {

        System.out.println("=================Webhook Received:===============");
        System.out.println("=================Webhook Received:===============");
        System.out.println("=================Webhook Received:===============");
        
        System.out.println(payload);

        System.out.println("=================Webhook Received:===============");
        System.out.println("=================Webhook Received:===============");
        System.out.println("=================Webhook Received:===============");
        
         WebhookEvent entity = new WebhookEvent();
         entity.setPayload(payload);
         webhookRepository.save(entity);

        ObjectMapper mapper = new ObjectMapper();
        JsonNode root = mapper.readTree(payload);


         // new logic for media type 

         String mediaId="";
         String caption="";
         String mimeType="";




   JsonNode message = root.path("entry")
        .path(0)
        .path("changes")
        .path(0)
        .path("value")
        .path("messages")
        .path(0);

            String type = message.path("type").asText();

            if ("image".equals(type)) {

                JsonNode image = message.path("image");

                 mediaId = image.path("id").asText();
                 caption = image.path("caption").asText("");
                 mimeType = image.path("mime_type").asText();

                System.out.println(mediaId);
                System.out.println(caption);
                System.out.println(mimeType);

            }

        //======================







        String phoneNumberId = root.get("entry").get(0).get("changes").get(0).get("value").get("contacts").get(0).get("wa_id").asText() != null ? root.get("entry").get(0).get("changes").get(0).get("value").get("contacts").get(0).get("wa_id").asText() :"";
        String profileName = root.get("entry").get(0).get("changes").get(0).get("value").get("contacts").get(0).get("profile").get("name").asText() != null ? root.get("entry").get(0).get("changes").get(0).get("value").get("contacts").get(0).get("profile").get("name").asText() : "";

        String messageBody = root.get("entry").get(0).get("changes").get(0).get("value").get("messages").get(0).get("text").get("body").asText() != null ? root.get("entry").get(0).get("changes").get(0).get("value").get("messages").get(0).get("text").get("body").asText() : "";





        if (!contactEntityRepository.existsByphonenumber(phoneNumberId)) {
                    ContactEntity  contactEntity = new ContactEntity();
                        contactEntity.setPayload(payload);
                        contactEntity.setTenantid(contactEntityRepository.getNextTenantId());
                        contactEntity.setWhatsappphonenumberid(contactEntityRepository.getNextWhatsappphonenumberId());
                        contactEntity.setPhonenumber(phoneNumberId);
                        contactEntity.setWhatsappprofilename(profileName);
                        contactEntity.setMessageBody(messageBody);
                        contactEntityRepository.save(contactEntity);
        }
        else{
            System.out.println(" Phone number must be unique");
        }


        



        ContactEntity contactEntity = contactEntityRepository.findBPhonenumber(phoneNumberId);
        ConversationEntity conversationEntity = new ConversationEntity();
        conversationEntity.setTenant_id(contactEntity.getTenantid());
        conversationEntity.setWhatsapp_phone_number_id(contactEntity.getWhatsappphonenumberid());
        conversationEntity.setPhonenumber(contactEntity.getPhonenumber());
        conversationEntity.setProfilename(contactEntity.getWhatsappprofilename());
        conversationEntity.setMessagestatus("Received");
        conversationEntity.setContact_id(contactEntity.getId());
        conversationEntity.setTitle("Default Title");
        conversationEntity.setStatus("Open");
        conversationEntity.setMessagebody(messageBody);
        conversationEntity.setMediaId(mediaId);
        conversationEntity.setCaption(caption);
        conversationEntity.setMimeType(mimeType);
        



       
        ConversationEntity  conversationEntityData =  conversationEntityRepository.save(conversationEntity);

       
        // ========================= Message Entity =============================
        //messageEntityRepository
        MessageEntity  messageEntity = new MessageEntity();
        messageEntity.setMediaid(messageEntityRepository.getNextMessageId());
        messageEntity.setTenantid(contactEntity.getTenantid());
        messageEntity.setWhatsappphonenumberid(contactEntity.getWhatsappphonenumberid());
        messageEntity.setContactid(contactEntity.getId());
        messageEntity.setPhonenumber(contactEntity.getPhonenumber());
        messageEntity.setProfilename(contactEntity.getWhatsappprofilename());
        messageEntity.setDirection("Inbound");
        messageEntity.setMessagetext(messageBody);
        messageEntity.setMessagebody(messageBody);
        messageEntity.setConversationentityid(conversationEntityData.getId());


        messageEntityRepository.save(messageEntity);


        // =======================================================================

        return ResponseEntity.ok("EVENT_RECEIVED");
    }

*/


@PostMapping
public ResponseEntity<String> receiveWebhook(@RequestBody String payload)
        throws JsonProcessingException {

    System.out.println("================ WEBHOOK RECEIVED ================");
    System.out.println(payload);
    System.out.println("==================================================");

    // Save raw payload
    WebhookEvent webhookEvent = new WebhookEvent();
    webhookEvent.setPayload(payload);
    webhookRepository.save(webhookEvent);

    ObjectMapper mapper = new ObjectMapper();
    JsonNode root = mapper.readTree(payload);

    JsonNode value = root.path("entry")
            .path(0)
            .path("changes")
            .path(0)
            .path("value");

    // Ignore status webhooks
    if (!value.has("messages")) {
        System.out.println("No message found. Probably status webhook.");
        return ResponseEntity.ok("EVENT_RECEIVED");
    }

    JsonNode message = value.path("messages").path(0);

    String type = message.path("type").asText("");

    String phoneNumberId = value.path("contacts")
            .path(0)
            .path("wa_id")
            .asText("");

    String profileName = value.path("contacts")
            .path(0)
            .path("profile")
            .path("name")
            .asText("");

    String messageBody = "";

    String mediaId = "";
    String caption = "";
    String mimeType = "";
    String filepath="";
    String fileName="";
    ResponseEntity<String> response=null;
    String chatboatdata="";

    switch (type) {

        case "text":
            messageBody = message.path("text")
                    .path("body")
                    .asText("");
            break;

        case "image":
            JsonNode image = message.path("image");

            mediaId = image.path("id").asText("");
            caption = image.path("caption").asText("");
            mimeType = image.path("mime_type").asText("");
              fileName = "";

            messageBody = caption;

            try
            {

          fileName=  new WhatsAppMediaDownloader().downloadMedia(mediaId,whatsappaccesstoken,fileName);
            }
            catch(Exception e)
            {
                System.out.println("===========================");
                System.out.println(" Error in saving the file "+e);
                System.out.println("===========================");
            }

            System.out.println("Image Received");
            System.out.println("Media Id : " + mediaId);
            System.out.println("Caption  : " + caption);
            System.out.println("MimeType : " + mimeType);
        break;
    case "document":
        JsonNode document = message.path("document");

        mediaId = document.path("id").asText();
        mimeType = document.path("mime_type").asText();
        fileName = document.path("filename").asText();


          try
            {

          fileName=  new WhatsAppMediaDownloader().downloadMedia(mediaId,whatsappaccesstoken,fileName);
            }
            catch(Exception e)
            {
                System.out.println("===========================");
                System.out.println(" Error in saving the file "+e);
                System.out.println("===========================");
            }

            System.out.println("Image Received");
            System.out.println("Media Id : " + mediaId);
            System.out.println("Caption  : " + caption);
            System.out.println("MimeType : " + mimeType);

        break;
                case "audio": {
        JsonNode audio = message.path("audio");

        mediaId = audio.path("id").asText();
        mimeType = audio.path("mime_type").asText();
      //  fileName = mediaId + getExtension(mimeType);

        try {
            fileName = new WhatsAppMediaDownloader()
                    .downloadMedia(mediaId, whatsappaccesstoken, fileName);
        } catch (Exception e) {
            System.out.println("Error saving audio: " + e.getMessage());
        }

        System.out.println("Audio Received");
        System.out.println("Media Id : " + mediaId);
        System.out.println("MimeType : " + mimeType);
        break;
    }

    case "video": {
        JsonNode video = message.path("video");

        mediaId = video.path("id").asText();
        mimeType = video.path("mime_type").asText();
       // fileName = mediaId + getExtension(mimeType);

        try {
            fileName = new WhatsAppMediaDownloader()
                    .downloadMedia(mediaId, whatsappaccesstoken, fileName);
        } catch (Exception e) {
            System.out.println("Error saving video: " + e.getMessage());
        }

        System.out.println("Video Received");
        System.out.println("Media Id : " + mediaId);
        System.out.println("MimeType : " + mimeType);
        break;
    }




        default:
            System.out.println("Unsupported message type : " + type);
            break;
    }

    // Save Contact
    if (!contactEntityRepository.existsByphonenumber(phoneNumberId)) {

        System.out.println("11111");
        System.out.println("11111");
        System.out.println("11111");
        System.out.println("11111");
        System.out.println("11111");
        System.out.println("11111");



        ContactEntity contactEntity1 = new ContactEntity();

        contactEntity1.setPayload(payload);
        contactEntity1.setTenantid(contactEntityRepository.getNextTenantId());
        contactEntity1.setWhatsappphonenumberid(contactEntityRepository.getNextWhatsappphonenumberId());
        contactEntity1.setPhonenumber(phoneNumberId);
        contactEntity1.setWhatsappprofilename(profileName);
        contactEntity1.setMessageBody(messageBody);
        contactEntity1.setHumanboatsetting("ENABLED_BOAT");

        contactEntityRepository.save(contactEntity1);

       

    } else {

        System.out.println("Contact already exists");

    }

    ContactEntity contactEntity =
            contactEntityRepository.findBPhonenumber(phoneNumberId);

         ContactEntity contactEntityData = contactEntityRepository.findBPhonenumber(phoneNumberId);


            if(contactEntityData != null && contactEntityData.getHumanboatsetting().equals("ENABLED_BOAT"))
            {   

                    try
                        {
                            String requestBody = String.format("""
                            {
                            "phone_number": "%s",
                            "message": "%s",
                            "profile_name": "%s",
                            "conversation_id": "1042"
                            }
                            """, phoneNumberId, messageBody, profileName);

                            response =    new SendReplyToChatBaot().sendData(requestBody,chatboaturl);

                                System.out.println(" chatbaot resposne ");
                                System.out.println(" chatbaot resposne ");
                                System.out.println(response);
                                System.out.println(" chatbaot resposne ");
                                System.out.println(" chatbaot resposne ");

                                System.out.println(" Chat boat respone ");
                                System.out.println(" Chat boat respone ");

                                System.out.println(response.getBody());
                                System.out.println(" Chat boat respone ");

                                ObjectMapper mapperchatboat = new ObjectMapper();
                                JsonNode node = mapperchatboat.readTree(response.getBody());

                                String reply = node.get("reply").asText();

                                System.out.println(reply);

                                servicesenddata.sendMessage(phoneNumberId, reply);

                                chatboatdata=response.getBody();
                                
                            //ChatBoatEntityRepository
                            
                            ChatBoatEntity  chatBoatEntity = new ChatBoatEntity();
                            chatBoatEntity.setPhonenumber(phoneNumberId)  ;
                            chatBoatEntity.setResponsepayload(response.getBody());
                            chatBoatEntity.setRequestpayload(requestBody);
                            chatBoatEntity.setPayload(payload);
                            
                            chatBoatEntityRepository.save(chatBoatEntity);
                        }


                        
                        catch(Exception e)
                        {
                                System.out.println(" error in sending chatbaot request ");
                                System.out.println(e);
                                System.out.println(" error in sending chatbaot request ");
                                
                        }
                        
            }






    // Save Conversation
    ConversationEntity conversation = new ConversationEntity();

    conversation.setTenant_id(contactEntity.getTenantid());
    conversation.setWhatsapp_phone_number_id(contactEntity.getWhatsappphonenumberid());
    conversation.setPhonenumber(contactEntity.getPhonenumber());
    conversation.setProfilename(contactEntity.getWhatsappprofilename());

    conversation.setMessagestatus("Received");
    conversation.setContact_id(contactEntity.getId());

    conversation.setTitle("Default Title");
    conversation.setStatus("Open");

    conversation.setMessagebody(messageBody);

    conversation.setMediaId(mediaId);
    conversation.setCaption(caption);
    conversation.setMimeType(mimeType);
    conversation.setChatbaotdata(chatboatdata);        


    ConversationEntity savedConversation =
            conversationEntityRepository.save(conversation);

    // Save Message
    MessageEntity messageEntity = new MessageEntity();

    messageEntity.setMessageid(messageEntityRepository.getNextMessageId());

    messageEntity.setTenantid(contactEntity.getTenantid());
    messageEntity.setWhatsappphonenumberid(contactEntity.getWhatsappphonenumberid());
    messageEntity.setContactid(contactEntity.getId());

    messageEntity.setPhonenumber(contactEntity.getPhonenumber());
    messageEntity.setProfilename(contactEntity.getWhatsappprofilename());

    messageEntity.setDirection("Inbound");

    messageEntity.setMessagetext(messageBody);
    messageEntity.setMessagebody(messageBody);

    messageEntity.setConversationentityid(savedConversation.getId());



    // Save image info
    messageEntity.setMediaid(mediaId);
    messageEntity.setCaption(caption);
    messageEntity.setMimetype(mimeType);
    messageEntity.setChatbaotdata(chatboatdata);

    messageEntityRepository.save(messageEntity);

   

             






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
