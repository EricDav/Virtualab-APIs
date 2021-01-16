<?php 
define('BASE_URL', '/v1');

class VirtualLab {
    const BASE_URL = '/v1';
    const PRODUCT_KEY_SIZE = 12;
    const ACTIVATION_KEY_SIZE = 10;
    const PIN_SIZE = 8;
    
    const ROUTES = [
        'POST' => array(
            BASE_URL . '/users' => 'User@create',
            BASE_URL . '/users/update' => 'User@update',
            BASE_URL . '/users/update/password' => 'User@updatePassword',
            BASE_URL . '/users/login' => 'User@login',
            BASE_URL . '/schools' => 'School@create',
            BASE_URL . '/classrooms' => 'School@addClass',
            BASE_URL . '/create-pins' => 'Pin@create',
            BASE_URL . '/activate' => 'Activation@activateByPin',
            BASE_URL . '/user-activation' => 'Activation@activate',
            BASE_URL . '/transfers' => 'User@transfer',
            BASE_URL . '/verify-payment' => 'User@verifyPayment',
            BASE_URL . '/classrooms/teachers' => 'School@assignTeacher',
            BASE_URL . '/registration' => 'User@registerAppUser',
            BASE_URL . '/registration/verify' => 'User@verifyAppUsers',
            BASE_URL . '/devices' => 'Device@create',
            BASE_URL . '/groups' => 'Group@create',
            BASE_URL . '/groups/edit' => 'Group@create',
            BASE_URL . '/groups/exit' => 'Group@exit',
        ),

        'GET' => array(
            BASE_URL . '/pins' => 'Pin@get',
            BASE_URL . '/schools' => 'School@get',
            BASE_URL . '/activations' => 'Activation@get',
            BASE_URL . '/transactions' => 'User@transactions',
            BASE_URL . '/users' => 'User@get',
            BASE_URL . '/classrooms' => 'School@getClassrooms',
            BASE_URL . '/user-details' => 'User@userDetails',
            BASE_URL . '/pins/history' => 'Pin@history',
            BASE_URL . '/teachers' => 'User@getTeachers',
            BASE_URL . '/downloads' => 'Download@download',
        )
    ];

    const ALLOWED_PARAM = [
        'POST' => array( 
            BASE_URL . '/users'  => ['name', 'email', 'password', 'phone_number'],
            BASE_URL . '/users/update'  => ['name', 'email', 'phone_number', 'token'],
            BASE_URL . '/users/update/password'  => ['new_password', 'old_password', 'token'],
            BASE_URL. '/users/login'  => ['email', 'password'],
            BASE_URL . '/schools' => ['name', 'phone_number', 'country', 'city', 'address', 'email', 'token'],
            BASE_URL . '/classrooms' => ['name', 'school_id', 'token'],
            BASE_URL . '/create-pins' => ['token', 'num_pins', 'amount'],
            BASE_URL . '/activate' => ['product_id', 'pin', 'pin_user'],
            BASE_URL . '/user-activation' => ['product_id', 'token'],
            BASE_URL . '/transfers' => ['email', 'amount', 'token'],
            BASE_URL . '/verify-payment' => ['user_id',  'ref'],
            BASE_URL . '/classrooms/teachers' => ['token', 'teacher_id', 'classroom_id'],
            BASE_URL . '/registration' => ['first_name',  'last_name', 'product_key', 'email', 'country'],
            BASE_URL . '/registration/verify' => ['token', 'product_key', 'email'],
            BASE_URL . '/devices' => ['product_key', 'property'],
            BASE_URL . '/groups' => ['group_name', 'email', 'product_key'],
            BASE_URL . '/groups/exit' => ['group_name', 'email', 'product_key'],
            BASE_URL . '/groups/edit' => ['group_id', 'email', 'product_key']
        ),
        'GET' => array(
            BASE_URL . '/pins' => ['token'],
            BASE_URL . '/schools' => ['token'],
            BASE_URL . '/activations' => ['token'],
            BASE_URL . '/transactions' => ['token'],
            BASE_URL . '/users' => ['token', 'role'],
            BASE_URL . '/classrooms' => ['token', 'school_id'],
            BASE_URL . '/user-details' => ['token'],
            BASE_URL . '/pins/history' => ['pin_id'],
            BASE_URL . '/teachers' => ['token'],
            BASE_URL . '/downloads' => ['code'],
        )
    ];
}
