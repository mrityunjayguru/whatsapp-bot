package com.whatsapp.app.model;


import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDateTime;

import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;
import org.springframework.http.ResponseEntity;

@Data
@Entity
@Table(name = "chatboatentity")
public class ChatBoatEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    private String phonenumber;

    @Column(columnDefinition = "TEXT")
    private String requestpayload;
    
    @Column(columnDefinition = "TEXT")
    private String responsepayload;

    
    @Column(columnDefinition = "TEXT")
    private String payload;

    @Column(name = "createdat", nullable = false)
    private LocalDateTime createdat = LocalDateTime.now();

    @Column(name = "updatedat", nullable = false)
    private LocalDateTime updatedat = LocalDateTime.now();

}
