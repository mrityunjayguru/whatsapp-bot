package com.whatsapp.app.Repository;

import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import com.whatsapp.app.model.Tags;

public interface TagsRepository extends JpaRepository<Tags, Long> {

/*
            CREATE SEQUENCE tags_table_tagid__seq
            START WITH 501
            INCREMENT BY 1;

      */

@Query("SELECT t FROM Tags t WHERE t.tagid IN :tagids")
List<Tags> findByTagid(@Param("tagids") List<String> tagids);

      @Query(value = "SELECT nextval('tags_table_tagid__seq')", nativeQuery = true)
      Long getNextTagId();

}
