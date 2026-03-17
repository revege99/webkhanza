<?php 
require_once 'bpjs_config.php';
// var_dump($cons_id);
// exit();

function generateBpjsHeaders($timestamp) {

    global $cons_id, $secret_key, $user_key_pcare, $auth, $signature;
    return [
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "X-authorization: $auth",
        "user_key: $user_key_pcare"
    ];
}

 ?>