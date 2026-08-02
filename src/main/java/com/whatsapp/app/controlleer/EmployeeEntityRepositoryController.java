package com.whatsapp.app.controlleer;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;

import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.whatsapp.app.Repository.EmployeeEntityRepository;
import com.whatsapp.app.model.EmployeeEntity;

@RestController
@RequestMapping("/api/employee")
public class EmployeeEntityRepositoryController {

    @Autowired
    EmployeeEntityRepository employeeEntityRepository;
    
    @PostMapping("/save")
public ResponseEntity<String> saveEmployee(@RequestBody EmployeeEntity employeeEntity) {
    try {
        if (employeeEntity.getId() != null &&
                employeeEntityRepository.existsById(employeeEntity.getId())) {

            // Update existing employee
            employeeEntityRepository.save(employeeEntity);
            return ResponseEntity.ok("Employee updated successfully.");

        } else {

            // Save new employee

             if (employeeEntity.getEmployeecode() == null || employeeEntity.getEmployeecode().isBlank()) {
                Long maxId = employeeEntityRepository.getMaxId();
                long nextId = (maxId == null) ? 1 : maxId + 1;

                employeeEntity.setEmployeecode(String.format("EMP-%02d", nextId));
                }


            employeeEntityRepository.save(employeeEntity);
            return ResponseEntity.status(HttpStatus.CREATED)
                    .body("Employee saved successfully.");
        }

    } catch (Exception e) {
        return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                .body("Operation failed: " + e.getMessage());
    }
}


    @GetMapping("/getall")
    public ResponseEntity<Iterable<EmployeeEntity>> allTag() {
        Iterable<EmployeeEntity> employeeEntity = employeeEntityRepository.findAll();
        return ResponseEntity.ok(employeeEntity);
    }

}
