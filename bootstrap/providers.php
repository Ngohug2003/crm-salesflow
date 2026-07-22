<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    RepositoryServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
];
