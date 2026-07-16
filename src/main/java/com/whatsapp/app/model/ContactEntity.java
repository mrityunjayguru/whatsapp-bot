package com.whatsapp.app.model;

import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDateTime;

import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

@Data
@Entity
@Table(
    name = "contactentity",
    uniqueConstraints = {
        @UniqueConstraint(name = "uk_tenantid", columnNames = "tenantid"),
        @UniqueConstraint(name = "uk_whatsapp_phone", columnNames = "whatsappphonenumberid"),
        @UniqueConstraint(name = "uk_phonenumber", columnNames = "phonenumber")
    }
)
public class ContactEntity {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "tenantid", nullable = false)
    private Long tenantid;

    @Column(name = "whatsappphonenumberid", nullable = false)
    private Long whatsappphonenumberid;

    @Column(name = "phonenumber", nullable = false)
    private String phonenumber;

    @Column(name = "whatsappprofilename")
    private String whatsappprofilename;

    @Column(name = "customname")
    private String customname;

    @Column(name = "email")
    private String email;

    @Column(name = "tags")
    private String tags;

    @Column(name = "createdat", nullable = false)
    private LocalDateTime createdat = LocalDateTime.now();

    @Column(name = "updatedat", nullable = false)
    private LocalDateTime updatedat = LocalDateTime.now();

    @JdbcTypeCode(SqlTypes.JSON)
    @Column(name = "payload", columnDefinition = "jsonb")
    private String payload;
}