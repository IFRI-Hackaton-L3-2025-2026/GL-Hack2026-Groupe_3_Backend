<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "AI4BMI API Groupe 3",
    version: "1.0.0",
    description: "API REST pour le système BMI — Gestion équipements et E-commerce BMI"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Serveur local"
)]

abstract class Controller
{
    //
}