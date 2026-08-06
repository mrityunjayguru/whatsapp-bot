package com.whatsapp.app.Repository;
import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import com.whatsapp.app.model.ConversationEntity;

public interface ConversationEntityRepository  extends JpaRepository<ConversationEntity, Long> {

    
          @Query("SELECT c FROM ConversationEntity c WHERE c.phonenumber = :phonenumber")
           List<ConversationEntity> findByPhonenumber(@Param("phonenumber") String phonenumber);

        @Query(value = """
        SELECT DISTINCT ON (phonenumber) *
        FROM conversationentity
        ORDER BY phonenumber, created_at DESC
        """, nativeQuery = true)
        List<ConversationEntity> findLatestConversationPerPhoneNumber();



    @Query(value = """
        SELECT *
        FROM public.conversationentity
        WHERE phonenumber = :phonenumber
          AND id >= (
              SELECT id
              FROM public.conversationentity
              WHERE phonenumber = :phonenumber
                AND messagestatus = 'Sent'
                AND status = 'Open'
              ORDER BY id DESC
              LIMIT 1
          )
        ORDER BY id
        """, nativeQuery = true)
    List<ConversationEntity> findConversationAfterLastSentOpen(
            @Param("phonenumber") String phonenumber);



}
