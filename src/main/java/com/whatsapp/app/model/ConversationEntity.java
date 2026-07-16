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

    private String tenant_id;
    private String whatsapp_phone_number_id;
    private String contact_id;
    private String assigned_user_id;
    private String status;
    private Integer unread_count;
    private String last_message_id;
    private String last_message_preview;
    private LocalDateTime last_message_at;
    private LocalDateTime first_message_at;
    private LocalDateTime created_at = LocalDateTime.now();
    private LocalDateTime updated_at = LocalDateTime.now();

    
    @JdbcTypeCode(SqlTypes.JSON)
    @Column(columnDefinition = "jsonb")
    private String payload;


}
