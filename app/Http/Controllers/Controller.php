<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[
    OA\Info(
        version: "1.0.0",
        title: "Chatbot & CRM Mobile API Documentation",
        description: "Official API Documentation for Company and Employee Mobile App (Chat, Contacts, Tags, and Conversations)."
    ),
    OA\Server(
        url: "/api",
        description: "API Server"
    ),
    OA\SecurityScheme(
        securityScheme: "bearerAuth",
        type: "http",
        scheme: "bearer",
        bearerFormat: "JWT",
        description: "Enter your Bearer token obtained from /api/login endpoint."
    )
]
abstract class Controller
{
    //
}