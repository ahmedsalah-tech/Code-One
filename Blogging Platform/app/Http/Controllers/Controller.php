<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        title: "Laravel Medium Clone API",
        version: "1.0.0",
        description: "API documentation for the Laravel Medium Clone project",
        contact: new OA\Contact(email: "admin@example.com")
    ),
    servers: [
        new OA\Server(url: "http://localhost:8000", description: "Local API Server")
    ],
    security: [
        ['csrf_token' => []]
    ]
)]
#[OA\SecurityScheme(
    securityScheme: "csrf_token",
    type: "apiKey",
    name: "X-CSRF-TOKEN",
    in: "header",
    description: "1. Open http://localhost:8000/csrf-token in a browser. 2. Copy the value of 'token'. 3. Click 'Authorize' here and paste that value."
)]
abstract class Controller
{
    //
}
