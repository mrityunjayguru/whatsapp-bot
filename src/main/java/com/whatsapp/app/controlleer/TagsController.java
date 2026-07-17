package com.whatsapp.app.controlleer;

import java.util.Arrays;
import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.whatsapp.app.Repository.TagsRepository;
import com.whatsapp.app.model.Tags;

@RestController
@RequestMapping("/api/tags")
public class TagsController {

    @Autowired
    private TagsRepository tagsRepository;

    @PostMapping
    public ResponseEntity<Tags> createTag(@RequestBody Tags tags) {

        tags.setTagid(tagsRepository.getNextTagId());
        Tags savedTag = tagsRepository.save(tags);
        return ResponseEntity.status(HttpStatus.CREATED).body(savedTag);
    }


    @GetMapping
    public ResponseEntity<Iterable<Tags>> allTag() {
        Iterable<Tags> tags = tagsRepository.findAll();
        return ResponseEntity.ok(tags);
    }


     @GetMapping("/bytagid/{tagid}")
    public ResponseEntity<?> getTagByTagId(@PathVariable String tagid) {
            List<String> tagids = Arrays.asList(tagid.split(","));
            List<Tags> tags = tagsRepository.findByTagid(tagids);
                    if (tags == null) {
                        return ResponseEntity.notFound().build();
                    }
                    return ResponseEntity.ok(tags);
                }
    }

      
