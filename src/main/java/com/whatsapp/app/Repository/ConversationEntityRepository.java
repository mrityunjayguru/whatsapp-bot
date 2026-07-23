package com.whatsapp.app.Repository;
import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

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


}
