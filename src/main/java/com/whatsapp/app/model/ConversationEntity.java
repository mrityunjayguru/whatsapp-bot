package com.whatsapp.app.model;


import jakarta.persistence.*;
import lombok.Data;

import java.time.LocalDateTime;

import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;


@Data
@Entity
@Table(name = "conversationentity")
public class ConversationEntity {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    private String phonenumber;
    private String profilename;
    private String messagestatus;
    private String messagebody;

    private Long tenant_id;
    private Long whatsapp_phone_number_id;
    private Long contact_id;
    private String title;
    private Long assigned_user_id;
    private String status;
    private Integer unread_count;
    private String last_message_id;
    private String last_message_preview;
    private LocalDateTime last_message_at;
    private LocalDateTime first_message_at;
    private LocalDateTime resolved_at;
    private LocalDateTime created_at = LocalDateTime.now();
    private LocalDateTime updated_at = LocalDateTime.now();

    
    @JdbcTypeCode(SqlTypes.JSON)
    @Column(columnDefinition = "jsonb")
    private String payload;


     private String messageId;

    private String mediaId;

    private String sender;

    private String caption;

    private String mimeType;

    private String filePath;

    private LocalDateTime receivedAt;


}
