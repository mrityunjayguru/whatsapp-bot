package com.whatsapp.app.Repository;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import com.whatsapp.app.model.EmployeeEntity;

public interface EmployeeEntityRepository extends JpaRepository<EmployeeEntity, Long>{
       @Query("SELECT MAX(e.id) FROM EmployeeEntity e")
         Long getMaxId();

}
