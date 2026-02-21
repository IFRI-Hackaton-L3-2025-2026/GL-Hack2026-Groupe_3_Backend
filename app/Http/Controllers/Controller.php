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
// Utilisation de la constante définie dans Render ou défaut local
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "Serveur de Production / Dynamique"
)]
abstract class Controller
{
    //
}