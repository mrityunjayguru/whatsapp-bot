package com.whatsapp.app.controlleer;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;

import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;


import com.whatsapp.app.Repository.DesignationMasterRepository;

import com.whatsapp.app.model.DesignationMaster;



@RestController
@RequestMapping("/api/designationmaster")
public class DesignationMasterRepositoryController {
    
    @Autowired
    DesignationMasterRepository designationMasterRepository;
    
    @PostMapping("/save")
public ResponseEntity<String> saveEmployee(@RequestBody DesignationMaster designationMaster) {
    try {
        if (designationMaster.getId() != null &&
                designationMasterRepository.existsById(designationMaster.getId())) {
                // Update existing designationMasterRepository
                designationMasterRepository.save(designationMaster);
                return ResponseEntity.ok("Designation master updated successfully.");

        } else {

            // Save new designationMasterRepository
                designationMasterRepository.save(designationMaster);
                return ResponseEntity.status(HttpStatus.CREATED)
                    .body("Designation master saved successfully.");
        }

    } catch (Exception e) {
        return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                .body("Operation failed: " + e.getMessage());
    }
}


    @GetMapping("/getalldesignation")
    public ResponseEntity<Iterable<DesignationMaster>> allTag() {
        Iterable<DesignationMaster> designationMaster = designationMasterRepository.findAll();
        return ResponseEntity.ok(designationMaster);
    }

}
