package com.whatsapp.app.model;

import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDateTime;

@Data
@Entity
@Table(name = "tags",uniqueConstraints = @UniqueConstraint(columnNames = "name"))
public class Tags {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    private String name;
    private Long tagid;
    private LocalDateTime createdat = LocalDateTime.now();
    private LocalDateTime updatedat = LocalDateTime.now();
}
