package com.whatsapp.app.configure;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.client.RestTemplate;
import org.springframework.web.servlet.config.annotation.CorsRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

@Configuration
public class AppConfig {
    @Bean
    public RestTemplate restTemplate() {
        return new RestTemplate();
    }

      @Bean
    public WebMvcConfigurer corsConfigurer() {

        System.out.println(" Config ");
        System.out.println(" Config ");
        System.out.println(" Config ");
        System.out.println(" Config ");
        System.out.println(" Config ");
        System.out.println(" Config ");



        return new WebMvcConfigurer() {
            @Override
            public void addCorsMappings(CorsRegistry registry) {
                registry.addMapping("/**")
                        .allowedOrigins("*") // Allow any origin
                        .allowedMethods("*") // GET, POST, PUT, DELETE, etc.
                        .allowedHeaders("*")
                        .allowCredentials(false);
            }
        };
    }
}