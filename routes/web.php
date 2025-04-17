<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
});

Route::get('/create-symlink', function () {
    symlink('/home/myusername/domains/mysub.domain.com/myappfolder/storage/app/public', '/home/myusername/domains/mysub.domain.com/public_html/storage');
    echo "Symlink Created. Thanks";
});
