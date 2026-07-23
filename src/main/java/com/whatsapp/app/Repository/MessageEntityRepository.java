package com.whatsapp.app.Repository;
import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import com.whatsapp.app.model.MessageEntity;

public interface MessageEntityRepository extends JpaRepository<MessageEntity, Long>{

    
      /*
            CREATE SEQUENCE messageid_seq
            START WITH 600
            INCREMENT BY 1;

      */

      @Query(value = "SELECT nextval('messageid_seq')", nativeQuery = true)
      Long getNextMessageId();

      @Query("SELECT c FROM MessageEntity c WHERE c.phonenumber = :phonenumber")
      List<MessageEntity> findByPhonenumber(@Param("phonenumber") String phonenumber);

}
