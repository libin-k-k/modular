<?php

use Illuminate\Support\Facades\Route;

Route::get('/enabled-module-ping', static function () {
    return 'enabled';
});
