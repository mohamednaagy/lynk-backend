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
        env('APP_URL') . './reset-password',
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
