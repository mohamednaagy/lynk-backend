<?php

return [
    /*
    |---------------------------------------------
    | Domains to allow
    | Leave empty to allow all requests
    |---------------------------------------------
    */
    'domains' => [
        //'*.example.com',
        env('APP_URL').'.api/v1/reset-password',
    ],

    /*
    |---------------------------------------------
    | Paths to exclude
    |---------------------------------------------
    */
    'excludes' => [
        //'/api/posts',
    ],
];
