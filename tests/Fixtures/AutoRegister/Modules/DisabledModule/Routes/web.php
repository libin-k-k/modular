<?php

use Illuminate\Support\Facades\Route;

Route::get('/disabled-module-ping', static function () {
    return 'disabled';
});
