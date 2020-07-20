<?php 

class VirtualLab {
    const BASE_URL = '/api/v1';
    const ROUTES = [
        'POST' => array(
            self::BASE_URL . '/users' => 'User@create',
            self::BASE_URL . '/users/login' => 'User@login'
        ),

        'GET' => array(

        )
    ];

    const ALLOWED_PARAM = [
        'POST' => array(
            self::BASE_URL . '/users'  => ['name', 'email', 'password'],
            self::BASE_URL. '/users/login'  => ['email', 'password'],
        ),
    ];
}
