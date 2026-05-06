<?php
date_default_timezone_set('UTC');

$cons_id    = "14494";
$secret_key = "6tXBDE443B";


$base_url   = "https://apijkn.bpjs-kesehatan.go.id/pcare-rest";
$auth_user  = "Dedi.kristina";
$auth_pass  = "SintLucia@123";
$kd_aplikasi = "095";


// userkey
$user_key_antrol   = '19d485ce5a10c80fb455c39ca25f4b89';
$user_key_pcare   = 'f9874c7a2cb354f832927ebdf95f6843';


// url
$url_panggil = "https://apijkn.bpjs-kesehatan.go.id/antreanfktp/antrean/panggil";


$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
$auth      = "Basic ".base64_encode("Dedi.kristina:SintLucia@123:095");