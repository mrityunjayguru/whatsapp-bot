package com.whatsapp.app.services;

import java.util.HashMap;
import java.util.Map;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

@Service
public class WhatsAppService {

    @Value("${whatsapp.access-token}")
    private String accessToken;

    @Value("${whatsapp.phone-number-id}")
    private String phoneNumberId;

    @Autowired
    private RestTemplate restTemplate;

    public String sendMessage(String to, String message) {

        System.out.println(" to "+to);
        System.out.println(" message "+message);
        System.out.println(" phoneNumberId "+phoneNumberId);
        System.out.println(" accessToken "+accessToken);
        

        String url = "https://graph.facebook.com/v23.0/" + phoneNumberId + "/messages";

        HttpHeaders headers = new HttpHeaders();
        headers.setBearerAuth(accessToken);
        headers.setContentType(MediaType.APPLICATION_JSON);
        Map<String, Object> body = new HashMap<>();
        body.put("messaging_product", "whatsapp");
        body.put("to", to);
        body.put("type", "text");

        Map<String, Object> text = new HashMap<>();
        text.put("preview_url", false);
        text.put("body", message);

        body.put("text", text);
                HttpEntity<Map<String, Object>> request = new HttpEntity<>(body, headers);

                return restTemplate.postForObject(url, request, String.class);
    }
}