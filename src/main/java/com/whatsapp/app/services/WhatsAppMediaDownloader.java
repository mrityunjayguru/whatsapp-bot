package com.whatsapp.app.services;


import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;

import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpMethod;
import org.springframework.http.ResponseEntity;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

public class WhatsAppMediaDownloader {

    public String downloadMedia(String mediaId,
                              String accessToken,
                              String originalFileName) throws IOException {

                                String fileName="";

        try {

            RestTemplate restTemplate = new RestTemplate();

            HttpHeaders headers = new HttpHeaders();
            headers.setBearerAuth(accessToken);

            HttpEntity<String> request = new HttpEntity<>(headers);

            // Get media information
            ResponseEntity<String> mediaResponse = restTemplate.exchange(
                    "https://graph.facebook.com/v23.0/" + mediaId,
                    HttpMethod.GET,
                    request,
                    String.class
            );

            ObjectMapper mapper = new ObjectMapper();
            JsonNode jsonNode = mapper.readTree(mediaResponse.getBody());

            String downloadUrl = jsonNode.path("url").asText();
            String mimeType = jsonNode.path("mime_type").asText();

            System.out.println("Download URL : " + downloadUrl);
            System.out.println("Mime Type    : " + mimeType);

            // Download file
            ResponseEntity<byte[]> fileResponse = restTemplate.exchange(
                    downloadUrl,
                    HttpMethod.GET,
                    request,
                    byte[].class
            );

            byte[] fileBytes = fileResponse.getBody();

            // Create folder
            Path directory = Paths.get("D:/whatsapp-files");
            Files.createDirectories(directory);

            // Decide filename
            

            if (originalFileName != null && !originalFileName.trim().isEmpty()) {
                fileName = originalFileName;
            } else {
                fileName = mediaId + getExtension(mimeType);
            }

            Path filePath = directory.resolve(fileName);

            Files.write(filePath, fileBytes);

            System.out.println("=================================");
            System.out.println("File Saved Successfully");
            System.out.println(filePath.toAbsolutePath());
            System.out.println("=================================");

        } catch (Exception e) {

            System.out.println("Error downloading media");
            e.printStackTrace();

        }

        return fileName;
    }

    private String getExtension(String mimeType) {

        if (mimeType == null || mimeType.isBlank()) {
            return ".bin";
        }

        switch (mimeType.toLowerCase()) {

            // Images
            case "image/jpeg":
                return ".jpg";

            case "image/png":
                return ".png";

            case "image/webp":
                return ".webp";

            case "image/gif":
                return ".gif";

            // Video
            case "video/mp4":
                return ".mp4";

            case "video/3gpp":
                return ".3gp";

            // Audio
            case "audio/mpeg":
                return ".mp3";

            case "audio/mp4":
                return ".m4a";

            case "audio/ogg":
                return ".ogg";

            case "audio/aac":
                return ".aac";

            // PDF
            case "application/pdf":
                return ".pdf";

            // CSV
            case "text/csv":
                return ".csv";

            // Text
            case "text/plain":
                return ".txt";

            // Word
            case "application/msword":
                return ".doc";

            case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
                return ".docx";

            // Excel
            case "application/vnd.ms-excel":
                return ".xls";

            case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
                return ".xlsx";

            // PowerPoint
            case "application/vnd.ms-powerpoint":
                return ".ppt";

            case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
                return ".pptx";

            // ZIP
            case "application/zip":
                return ".zip";

            case "application/x-rar-compressed":
                return ".rar";

            default:

                if (mimeType.contains("/")) {
                    return "." + mimeType.substring(mimeType.indexOf("/") + 1);
                }

                return ".bin";
        }
    }
}