package com.whatsapp.app.controlleer;


import java.nio.charset.StandardCharsets;

import java.util.Base64;
import java.util.LinkedList;
import java.util.List;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

@RestController
@RequestMapping("/api")
public class WebhookDataResponseController {

     private final RestTemplate restTemplate = new RestTemplate();
    private final ObjectMapper mapper = new ObjectMapper();





     @GetMapping("/webhookdataresponse/{id}")
    public ResponseEntity<?> allTag(@PathVariable String id) throws Exception {
        
     
        String url = "http://127.0.0.1:4040/api/requests/http/"+id;

        String response = restTemplate.getForObject(url, String.class);

        JsonNode root = mapper.readTree(response);

        // Get Base64 encoded raw request
        String raw = root.path("request").path("raw").asText();

        // Decode Base64
        String decoded = new String(Base64.getDecoder().decode(raw), StandardCharsets.UTF_8);

        // Find start of HTTP body
        int index = decoded.indexOf("\r\n\r\n");
        int skip = 4;

        if (index == -1) {
            index = decoded.indexOf("\n\n");
            skip = 2;
        }

        String jsonPayload = decoded.substring(index + skip);

        return ResponseEntity.ok(jsonPayload);
    }

@GetMapping("/allwebhookrequest")
public ResponseEntity<?> getAllRequest() throws Exception {

    String listUrl = "http://127.0.0.1:4040/api/requests/http";

    String response = restTemplate.getForObject(listUrl, String.class);

    JsonNode root = mapper.readTree(response);

    List<String> result = new LinkedList<>();

    JsonNode requests = root.path("requests");

    for (JsonNode requestNode : requests) {

        String requestId = requestNode.path("id").asText();

        System.out.println("=======niraj == requestId " + requestId);

        String detailUrl =
                "http://127.0.0.1:4040/api/requests/http/" + requestId;

        String detailResponse =
                restTemplate.getForObject(detailUrl, String.class);


                 JsonNode rootdata = mapper.readTree(detailResponse);

        // Get Base64 encoded raw request
        String raw = rootdata.path("request").path("raw").asText();

        // Decode Base64
        String decoded = new String(Base64.getDecoder().decode(raw), StandardCharsets.UTF_8);

        // Find start of HTTP body
        int index = decoded.indexOf("\r\n\r\n");
        int skip = 4;

        if (index == -1) {
            index = decoded.indexOf("\n\n");
            skip = 2;
        }

        String jsonPayload = decoded.substring(index + skip);


        result.add(jsonPayload);
    }

    return ResponseEntity.ok(result);
}

    }


