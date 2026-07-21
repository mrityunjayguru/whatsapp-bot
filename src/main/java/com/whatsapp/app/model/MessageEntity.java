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
    private Long mediaid;
    private String mediaurl;
    private String mimetype;
    private String filename;
    private String caption;
    private String status;
    private String failurereason;
    private Boolean isdeleted;
    private Boolean isforwarded;
    private Boolean isstarred;
    private Boolean isedited;
    private LocalDateTime sentat;
    private LocalDateTime deliveredat;
    private LocalDateTime read_at;
    private LocalDateTime createdat = LocalDateTime.now();
    private LocalDateTime updatedat = LocalDateTime.now();

    private String phonenumber;
    private String messagebody;
    private String profilename;



}
