<?php
date_default_timezone_set('UTC');

$cons_id    = "13216";
$secret_key = "3nG5007800";


$base_url   = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev";
$auth_user  = "0373B006.icare";
$auth_pass  = "LebihH1dup!";
$kd_aplikasi = "095";


// userkey
$user_key_antrol   = '907eacdff6474399dafd7c60d4b13c0a';
$user_key_pcare   = 'f126b8a2c2488a9eec8e79fdd0bd55ef';


// url
$url_panggil = "https://apijkn-dev.bpjs-kesehatan.go.id/antreanfktp_dev/antrean/panggil";


$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
$auth      = "Basic ".base64_encode("0373B006.pcare:LebihH1dup!:095");