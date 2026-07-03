package com.whatsapp.app.services;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Service
public class WhatsAppService {

    @Autowired
    private RestTemplate restTemplate;

    public String sendTemplateWithParams(String to, String templateName, List<String> parametersList) {
        String phoneNumberId = "1216945994830075";
        String accessToken = "EAAZALVnOwhKABR6...[Your Full Token]"; 
        String url = "https://graph.facebook.com/v23.0/" + phoneNumberId + "/messages";

        HttpHeaders headers = new HttpHeaders();
        headers.setBearerAuth(accessToken);
        headers.setContentType(MediaType.APPLICATION_JSON);

        // Core Meta JSON Structure
        Map<String, Object> body = new HashMap<>();
        body.put("messaging_product", "whatsapp");
        body.put("to", to);
        body.put("type", "template");

        Map<String, Object> template = new HashMap<>();
        template.put("name", templateName);

        Map<String, String> language = new HashMap<>();
        language.put("code", "en_US");
        template.put("language", language);

        // Map list strings to Meta Parameter Objects: {"type": "text", "text": "value"}
        List<Map<String, Object>> parameters = new ArrayList<>();
        for (String textValue : parametersList) {
            Map<String, Object> param = new HashMap<>();
            param.put("type", "text");
            param.put("text", textValue);
            parameters.add(param);
        }

        // Place parameter array inside the text "body" component context
        Map<String, Object> bodyComponent = new HashMap<>();
        bodyComponent.put("type", "body");
        bodyComponent.put("parameters", parameters);

        List<Map<String, Object>> components = new ArrayList<>();
        components.add(bodyComponent);

        template.put("components", components);
        body.put("template", template);

        // Execute Post request via HttpEntity wrapper
        HttpEntity<Map<String, Object>> entity = new HttpEntity<>(body, headers);
        ResponseEntity<String> response = restTemplate.postForEntity(url, entity, String.class);
        
        return response.getBody();
    }
}
