package com.whatsapp.app.controlleer;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import com.whatsapp.app.Repository.RoleMasterRepository;
import com.whatsapp.app.model.RoleMaster;


@RestController
@RequestMapping("/api/rolemaster")
public class RoleMasterRepositoryController {
    
    @Autowired
    RoleMasterRepository roleMasterRepository;
    
    @PostMapping("/save")
public ResponseEntity<String> saveRoleMaster(@RequestBody RoleMaster roleMaster) {
    try {
        if (roleMaster.getId() != null &&
                roleMasterRepository.existsById(roleMaster.getId())) {
                // Update existing roleMasterRepository
                roleMasterRepository.save(roleMaster);
                return ResponseEntity.ok("Role master updated successfully.");

        } else {

            // Save new roleMasterRepository
                roleMasterRepository.save(roleMaster);
                return ResponseEntity.status(HttpStatus.CREATED)
                    .body("Role master saved successfully.");
        }

    } catch (Exception e) {
        return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                .body("Operation failed: " + e.getMessage());
    }
}


    @GetMapping("/getallroles")
    public ResponseEntity<Iterable<RoleMaster>> allTag() {
        Iterable<RoleMaster> roleMaster = roleMasterRepository.findAll();
        return ResponseEntity.ok(roleMaster);
    }

}

