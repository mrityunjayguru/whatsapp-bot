package com.whatsapp.app.model;


import jakarta.persistence.*;
import lombok.Data;

import java.time.LocalDateTime;


@Data
@Entity
@Table(name = "employeeentity")
public class EmployeeEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    private String firstname;
    private String lastname;
    @Column(name = "email", nullable = false, unique = true)
    private String email;
    private String mobile;
    private Long tenantid;
    private String  employeecode;
    private String displayname; 
    private String password_hash;
    private String profilephoto;
    private String  designation;
    private String department;
    private String role;
    private String status;
    private LocalDateTime  lastloginat;
    private String is_online;	
    private Long assignedconversationcount;
    private Long resolvedconversationcount;
    private String createdby;
    private LocalDateTime createdat = LocalDateTime.now();;	
    private LocalDateTime updatedat = LocalDateTime.now();;
    
}
