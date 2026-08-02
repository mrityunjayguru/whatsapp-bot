package com.whatsapp.app.model;

import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDateTime;

@Data
@Entity
@Table(name = "settingentity")
public class SettingEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    private Boolean humanenabled;
    private Boolean boatenabled;  
    @Column(name = "createdat", nullable = false)
    private LocalDateTime createdat = LocalDateTime.now();
    @Column(name = "updatedat", nullable = false)
    private LocalDateTime updatedat = LocalDateTime.now();    
}
