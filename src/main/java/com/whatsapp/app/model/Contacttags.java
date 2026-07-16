package com.whatsapp.app.model;

import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDateTime;

@Data
@Entity
@Table(
    name = "contacttags",
    uniqueConstraints = {
        @UniqueConstraint(
            name = "uk_contact_tag",
            columnNames = {"contactid", "tagid"}
        )
    }
)
public class Contacttags {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private Long contactid;

    @Column(nullable = false)
    private Long tagid;

    private LocalDateTime createdat = LocalDateTime.now();
    private LocalDateTime updatedat = LocalDateTime.now();
}