<?php

use Illuminate\Support\Facades\Route;
use Modules\Car\CarController;

Route::resource('cars', CarController::class);
