package com.whatsapp.app.model;

import java.time.LocalDateTime;

import jakarta.persistence.*;
import lombok.Data;

@Data
@Entity
@Table(
    name = "rolemaster",
    uniqueConstraints = {
        @UniqueConstraint(
            name = "uk_rolemaster_rolename",
            columnNames = "rolename"
        )
    }
)
public class RoleMaster {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, unique = true)
    private String rolename;

    private String createdby;
    
    @Column(name = "createdat", nullable = false)
    private LocalDateTime createdat = LocalDateTime.now();
    @Column(name = "updatedat", nullable = false)
    private LocalDateTime updatedat = LocalDateTime.now();


}
