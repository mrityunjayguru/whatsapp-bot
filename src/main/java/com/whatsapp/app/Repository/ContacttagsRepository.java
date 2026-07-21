package com.whatsapp.app.Repository;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import com.whatsapp.app.model.ContactEntity;
import com.whatsapp.app.model.Contacttags;

public interface ContacttagsRepository extends JpaRepository<Contacttags, Long>{


}
