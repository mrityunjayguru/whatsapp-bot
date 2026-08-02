package com.whatsapp.app.services;
import org.springframework.http.*;
import org.springframework.web.client.RestTemplate;

public class SendReplyToChatBaot {

    

    public  ResponseEntity<String> sendData(String requestBody, String chatboaturl) {
        RestTemplate restTemplate = new RestTemplate();
        System.out.println(" url ");
        System.out.println(chatboaturl);
        System.out.println(requestBody);
        System.out.println("requestBody");



        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);

        

        HttpEntity<String> request = new HttpEntity<>(requestBody, headers);

        ResponseEntity<String> response = restTemplate.postForEntity(
                chatboaturl,
                request,
                String.class
        );

        System.out.println("Status: " + response.getStatusCode());
        System.out.println("Body: " + response.getBody());

        return response;
    }


}
