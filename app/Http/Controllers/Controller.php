<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'BMI API',
    version: '1.0.0',
    description: 'Documentation de l\'API BMI — Module Equipements & E-commerce'
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Serveur local'
)]
abstract class Controller
{
    //
}