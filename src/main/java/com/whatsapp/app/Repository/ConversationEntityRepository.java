package com.whatsapp.app.Repository;
import org.springframework.data.jpa.repository.JpaRepository;
 
import com.whatsapp.app.model.ConversationEntity;

public interface ConversationEntityRepository  extends JpaRepository<ConversationEntity, Long> {

}
