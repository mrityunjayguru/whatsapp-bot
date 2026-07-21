package com.whatsapp.app.Repository;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import com.whatsapp.app.model.MessageEntity;

public interface MessageEntityRepository extends JpaRepository<MessageEntity, Long>{

    
      /*
            CREATE SEQUENCE messageid_seq
            START WITH 600
            INCREMENT BY 1;

      */

      @Query(value = "SELECT nextval('messageid_seq')", nativeQuery = true)
      Long getNextMessageId();
}
