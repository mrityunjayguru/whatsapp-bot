package com.whatsapp.app.Repository;

import org.springframework.data.jpa.repository.JpaRepository;

import com.whatsapp.app.model.WebhookEvent;

public interface WebhookRepository extends JpaRepository<WebhookEvent, Long> {
}
