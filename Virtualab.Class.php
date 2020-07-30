<?php 
define('BASE_URL', '/api/v1');

class VirtualLab {
    const BASE_URL = '/api/v1';
    const ROUTES = [
        'POST' => array(
            BASE_URL . '/users' => 'User@create',
            BASE_URL . '/users/login' => 'User@login',
            BASE_URL . '/schools' => 'School@create',
            BASE_URL . '/class-rooms' => 'School@addClass',
        ),

        'GET' => array(

        )
    ];

    const ALLOWED_PARAM = [
        'POST' => array(
            BASE_URL . '/users'  => ['name', 'email', 'password'],
            BASE_URL. '/users/login'  => ['email', 'password'],
            BASE_URL . '/schools' => ['name', 'phone_number', 'country', 'city', 'address', 'token'],
            BASE_URL . '/class-rooms' => ['name', 'school_id'],
        ),
    ];
}
