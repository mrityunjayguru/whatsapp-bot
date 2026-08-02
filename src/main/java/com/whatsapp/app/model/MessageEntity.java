package com.whatsapp.app.model;

import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDateTime;

@Data
@Entity
@Table(name = "messageentity")
public class MessageEntity {
    
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    private Long messageid;
    private Long tenantid;
    private Long conversationid;
    private Long contactid;
    private Long whatsappphonenumberid;
    private Long metamessageid;
    private Long replytometamessageid;
    private String messagetype;
    private String direction;
    private String sendertype;
    private Long tenantuserid;
    private String messagetext;
    private String mediaid;
    private String mediaurl;
    private String mimetype;
    private String filename;
    private String caption;
    private String status="open";
    private String failurereason;
    private Boolean isdeleted;
    private Boolean isforwarded;
    private Boolean isstarred;
    private Boolean isedited;
    private LocalDateTime sentat = LocalDateTime.now();
    private LocalDateTime deliveredat=LocalDateTime.now();
    private LocalDateTime read_at= LocalDateTime.now();
    private LocalDateTime createdat = LocalDateTime.now();
    private LocalDateTime updatedat = LocalDateTime.now();

    private String phonenumber;

    @Column(columnDefinition = "TEXT")
    private String messagebody;

    private String profilename;
    private Long conversationentityid;



    private String sender;

    


    private String filepath;

    private LocalDateTime receivedAt = LocalDateTime.now();

    @Column(columnDefinition = "TEXT")
    private String chatbaotdata;




}
