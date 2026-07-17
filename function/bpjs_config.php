<?php
date_default_timezone_set('UTC');

$cons_id    = "8858";
$secret_key = "1aQ28BEF29";


$base_url   = "https://apijkn.bpjs-kesehatan.go.id/pcare-rest";
$auth_user  = "0024B012.lemsa";
$auth_pass  = "Situmorang100226*";
$kd_aplikasi = "095";


// userkey
$user_key_antrol   = 'e60d8b0e2cdd7d59e5876b7134a23c98';
$user_key_pcare   = 'c1b21d6f327be5b371692cf50a869b49';


// url
$url_panggil = "https://apijkn.bpjs-kesehatan.go.id/antreanfktp/antrean/panggil";


$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
$auth      = "Basic ".base64_encode("0024B012.lemsa:Situmorang100226*:095");