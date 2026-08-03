package com.whatsapp.app.model;

import java.time.LocalDateTime;

import jakarta.persistence.*;
import lombok.Data;

@Data
@Entity
@Table(
    name = "designationmaster",
    uniqueConstraints = {
        @UniqueConstraint(
            name = "uk_designationmaster_designationname",
            columnNames = "designationname"
        )
    }
)
public class DesignationMaster {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, unique = true)
    private String designationname;
    

    private String createdby;
    
    @Column(name = "createdat", nullable = false)
    private LocalDateTime createdat = LocalDateTime.now();
    @Column(name = "updatedat", nullable = false)
    private LocalDateTime updatedat = LocalDateTime.now();


}
