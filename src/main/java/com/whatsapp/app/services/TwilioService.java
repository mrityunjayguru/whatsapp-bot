package com.whatsapp.app.services;

import com.twilio.Twilio;
import com.twilio.rest.api.v2010.account.Message;
import com.twilio.type.PhoneNumber;
import jakarta.annotation.PostConstruct;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

@Service
public class TwilioService {

    @Value("${twilio.account.sid}")
    private String accountSid;

    @Value("${twilio.auth.token}")
    private String authToken;

    @Value("${twilio.whatsapp.number}")
    private String fromWhatsAppNumber;

    @PostConstruct
    public void init() {
        Twilio.init(accountSid, authToken);
    }

    public String sendWhatsAppMessage(String to, String messageBody) {

        Message message = Message.creator(
                new PhoneNumber("whatsapp:" + to),
                new PhoneNumber(fromWhatsAppNumber),
                messageBody
        ).create();

        return "SID: " + message.getSid()
        + ", Status: " + message.getStatus()
        + ", To: " + message.getTo();
    }
}