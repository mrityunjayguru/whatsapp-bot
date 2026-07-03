package com.whatsapp.app.controlleer;

import com.whatsapp.app.services.TwilioService;
import com.whatsapp.app.services.WhatsAppService;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/whatsapp")
public class WhatsAppController {

    @Autowired
    private TwilioService twilioService;

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
        return service.sendMessage(to, message);
    }


}