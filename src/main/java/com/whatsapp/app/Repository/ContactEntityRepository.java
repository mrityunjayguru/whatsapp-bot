package com.whatsapp.app.Repository;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import com.whatsapp.app.model.ContactEntity;

public interface ContactEntityRepository extends JpaRepository<ContactEntity, Long> {
 boolean existsByphonenumber(String phonenumber);


        /*
            CREATE SEQUENCE tenant_seq
            START WITH 1000
            INCREMENT BY 1;
        */

            @Query("SELECT c FROM ContactEntity c WHERE c.phonenumber = :phonenumber")
            ContactEntity findBPhonenumber(@Param("phonenumber") String phonenumber);



            @Query("SELECT c FROM ContactEntity c WHERE c.whatsappphonenumberid = :whatsappphonenumberid")
            ContactEntity findByWhatsappphonenumberid(@Param("whatsappphonenumberid") Long whatsappphonenumberid);


        @Query(value = "SELECT nextval('tenant_seq')", nativeQuery = true)
        Long getNextTenantId();

      /*
            CREATE SEQUENCE whatsappphonenumberid_seq
            START WITH 1000
            INCREMENT BY 1;

      */

      @Query(value = "SELECT nextval('whatsappphonenumberid_seq')", nativeQuery = true)
      Long getNextWhatsappphonenumberId();

}

