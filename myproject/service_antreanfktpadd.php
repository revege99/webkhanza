<?php
date_default_timezone_set('Asia/Jakarta');
putenv('TZ=Asia/Jakarta');

require_once __DIR__ . '/../function/function_klinik.php';
require_once __DIR__ . '/vendor/autoload.php';

use LZCompressor\LZString;

// ---------- Konfigurasi BPJS ----------
$cons_id    = '13505';
$secret_key = '9hQE24BFE8';
$user_key   = 'f68d3658bf3e2d1435ffff5397c3e9f0';
$url_add    = "https://apijkn.bpjs-kesehatan.go.id/antreanfktp/antrean/add";

// ---------- Fungsi Nomor Antrean ----------
function nextQueueNumber($kodePoli, $tanggal) {
    // Bisa diubah sesuai logika antrean nyata
    return ['angka' => 1, 'nomor' => 'A-1'];
}

// ---------- Fungsi Log ----------
function writeLog($message) {
    $dir = __DIR__ . '/logs';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    $logFile = $dir . '/antrean_' . date('Y-m-d') . '.log';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "$timestamp $message" . PHP_EOL, FILE_APPEND);
    echo $message . PHP_EOL;
}

// ---------- Start ----------
writeLog("=== Service Antrean BPJS siap dijalankan ===");

while (true) {
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

    if ($now->format('H:i') >= '07:00' && $now->format('H:i') <= '23:00') {
        $tanggal = $now->format("Y-m-d");
        writeLog("Cek pasien BPJS tanggal $tanggal ...");

        // ---------- Query pasien yang belum dikirim ----------
        $query = "
        SELECT 
        rp.no_rawat, 
        rp.no_reg AS nomor, 
        RIGHT(rp.no_reg,3) AS angka, 
        ps.nm_pasien, 
        ps.no_tlp AS nohp, 
        ps.no_rkm_medis,
        ps.no_peserta, 
        ps.no_ktp, 
        mpp.kd_poli_pcare AS kd_poli, 
        pk.nm_poli, 
        mdk.kd_dokter_pcare, 
        d.nm_dokter,
        CONCAT(j.jam_mulai,'-',j.jam_selesai) AS jam_praktek
    FROM reg_periksa rp
    INNER JOIN poliklinik pk ON rp.kd_poli = pk.kd_poli
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    INNER JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
    INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    INNER JOIN jadwal j ON rp.kd_dokter = j.kd_dokter
    LEFT JOIN antrean_terkirim_bpjs atb ON rp.no_rawat = atb.no_rawat
    INNER JOIN maping_dokter_pcare mdk ON d.kd_dokter = mdk.kd_dokter
    INNER JOIN maping_poliklinik_pcare mpp ON rp.kd_poli = mpp.kd_poli_rs
    WHERE mpp.kd_poli_pcare IN ('001','U0010','U0035', '003')
      AND rp.kd_pj = 'bpj'
      AND rp.tgl_registrasi = ?
      AND (
            atb.no_rawat IS NULL   
            OR atb.status_code != '200' 
          )
    GROUP BY rp.no_rawat;
        ";

        $cari_pasien = queryPrepared($query, [$tanggal]);

        if (empty($cari_pasien)) {
            writeLog("Tidak ada pasien BPJS baru hari ini.");
        } else {
            foreach ($cari_pasien as $data) {
                try {
                    $queue = nextQueueNumber($data['kd_poli'], $tanggal);
                    list($mulai, $selesai) = explode('-', $data['jam_praktek']);
                    $jam_praktek = date('H:i', strtotime($mulai)) . '-' . date('H:i', strtotime($selesai));

                    $payload = [
                        "nomorkartu"     => $data['no_peserta'],
                        "nik"            => $data['no_ktp'],
                        "nohp"           => $data['nohp'],
                        "kodepoli"       => $data['kd_poli'],
                        "namapoli"       => $data['nm_poli'],
                        "norm"           => $data['no_rkm_medis'],
                        "tanggalperiksa" => $tanggal,
                        "kodedokter"     => $data['kd_dokter_pcare'],
                        "namadokter"     => $data['nm_dokter'],
                        "jampraktek"     => $jam_praktek,
                        "nomorantrean"   => $data['nomor'],
                        "angkaantrean"   => $data['angka'],
                        "keterangan"     => ""
                    ];

                    // var_dump($payload);
                    // exit();

                    $timestamp = time();
                    $signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
                    $auth      = "Basic " . base64_encode("tester.stmartina:Bpjs123**:095");

                    $headers = [
                        "Content-Type: application/json",
                        "X-cons-id: $cons_id",
                        "X-timestamp: $timestamp",
                        "X-signature: $signature",
                        "user_key: $user_key",
                        "X-authorization: $auth"
                    ];

                    $ch = curl_init($url_add);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    $response = curl_exec($ch);
                    curl_close($ch);

                    if (curl_errno($ch)) {
                        writeLog("Curl error: " . curl_error($ch));
                        continue;
                    }

                    $decoded = json_decode($response, true);
                    $code = $decoded['metadata']['code'] ?? 0;
                    $message = $decoded['metadata']['message'] ?? 'Tidak ada pesan';

                    $status = ($code == 200) ? 'sukses' : 'gagal';

                    writeLog("Pasien dikirim: " . $data['nm_pasien'] . " => Status: $status ($code) | Pesan: $message");

                    // ---------- Simpan ke tabel ----------
                    $insert = "
                        INSERT INTO antrean_terkirim_bpjs 
                            (no_rawat, tgl_kirim, response, status_code, message)
                        VALUES 
                            (?, NOW(), ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            response = VALUES(response),
                            tgl_kirim = NOW(),
                            status_code = VALUES(status_code),
                            message = VALUES(message)
                    ";

                    queryPrepared($insert, [
                    $data['no_rawat'], // string
                    $response,         // json response
                    $code,             // HTTP status code (INT)
                    $message           // pesan dari BPJS
                ]);


                } catch (Exception $e) {
                    writeLog("Error simpan pasien " . $data['nm_pasien'] . ": " . $e->getMessage());
                }

                sleep(1); // delay antar pasien
            }
        }

        sleep(5); // delay cek pasien baru
    } else {
        sleep(30); // di luar jam kerja
    }
}
