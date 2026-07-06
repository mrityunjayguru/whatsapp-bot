package com.whatsapp.app.controlleer;

import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;

@Controller
public class HomeController {

    @GetMapping("/")
    public String home(Model model) {

        System.out.println(" HHHHHHHHHHHHHHHHHHome");
        model.addAttribute("message", "Hello from Spring Boot Controller!");
        return "home";
    }
}