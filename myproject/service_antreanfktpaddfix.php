<?php
date_default_timezone_set('Asia/Jakarta');
putenv('TZ=Asia/Jakarta');

require_once __DIR__ . '/../../webkhanza/function/function_klinik.php';
require_once __DIR__ . '/../../webkhanza/myproject/vendor/autoload.php';

// ---------- Konfigurasi BPJS ----------
$cons_id    = '14494';
$secret_key = '6tXBDE443B';
$user_key   = '19d485ce5a10c80fb455c39ca25f4b89';
$url_add    = "https://apijkn.bpjs-kesehatan.go.id/antreanfktp/antrean/add";

// ---------- Fungsi Nomor Antrean ----------
function nextQueueNumber($kodePoli, $tanggal) {

    // Mapping prefix antrean (bisa disesuaikan)
    $prefix = 'A';
    if ($kodePoli == 'U0010') {
        $prefix = 'B';
    }

    $sql = "
        SELECT MAX(RIGHT(no_reg, 3)) AS max_angka
        FROM reg_periksa
        WHERE kd_poli = ?
          AND tgl_registrasi = ?
    ";

    $result = queryPrepared($sql, [$kodePoli, $tanggal]);

    $angka = 1;
    if (!empty($result) && $result[0]['max_angka'] !== null) {
        $angka = (int)$result[0]['max_angka'] + 1;
    }

    $nomor = $prefix . '-' . $angka;

    return [
        'angka' => $angka,
        'nomor' => $nomor
    ];
}

// ---------- Fungsi Log ----------
function writeLog($message) {
    $dir = __DIR__ . '/../../webkhanza/myproject/logs';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    $logFile = $dir . '/antrean_' . date('Y-m-d') . '.log';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "$timestamp $message" . PHP_EOL, FILE_APPEND);
    echo $message . PHP_EOL;
}

function getHariIndonesiaByTanggal(string $tanggal): ?string {
    $hariInggris = date('l', strtotime($tanggal));

    $map = [
        'Monday'    => 'SENIN',
        'Tuesday'   => 'SELASA',
        'Wednesday' => 'RABU',
        'Thursday'  => 'KAMIS',
        'Friday'    => 'JUMAT',
        'Saturday'  => 'SABTU',
        'Sunday'    => 'MINGGU'
    ];

    return $map[$hariInggris] ?? null;
}

function normalizeBpjsMessage(?string $message): string {
    $normalized = strtolower(trim((string)$message));

    return preg_replace('/\s+/', ' ', $normalized) ?? '';
}

function isTerminalBpjsStatus($statusCode, ?string $message): bool {
    $normalizedMessage = normalizeBpjsMessage($message);
    $statusCode = (int)$statusCode;

    if ($statusCode === 200 && $normalizedMessage === 'ok') {
        return true;
    }

    return $statusCode === 201
        && $normalizedMessage === 'peserta sudah terdaftar di poli tersebut pada hari ini';
}

function isScreeningPendingMessage(?string $message): bool {
    $normalizedMessage = normalizeBpjsMessage($message);

    return $normalizedMessage === 'anda belum melakukan skrining kesehatan. mohon untuk melakukan skrining kesehatan terlebih dahulu pada menu skrining kesehatan.'
        || str_contains($normalizedMessage, 'belum melakukan skrining kesehatan');
}

function saveBpjsQueueResult(array $data, string $response, int $code, string $message): bool {
    $existingId = trim((string)($data['antrean_bpjs_id'] ?? ''));

    if ($existingId !== '') {
        return queryPrepared(
            "
                UPDATE antrean_terkirim_bpjs
                SET
                    tgl_kirim = NOW(),
                    response = ?,
                    status_code = ?,
                    message = ?
                WHERE id = ?
            ",
            [$response, (string)$code, $message, $existingId]
        ) === true;
    }

    return queryPrepared(
        "
            INSERT INTO antrean_terkirim_bpjs
                (no_rawat, tgl_kirim, response, status_code, message)
            VALUES
                (?, NOW(), ?, ?, ?)
        ",
        [$data['no_rawat'], $response, (string)$code, $message]
    ) === true;
}

// ---------- Start ----------
writeLog("=== Service Antrean BPJS siap dijalankan ===");

while (true) {
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

    if ($now->format('H:i') >= '04:00' && $now->format('H:i') <= '23:00') {

        $tanggal = $now->format("Y-m-d");
        $hari    = getHariIndonesiaByTanggal($tanggal);

        writeLog("Cek pasien BPJS tanggal $tanggal ($hari) ...");

        $query = "
            SELECT
                rp.no_rawat,
                rp.no_reg AS nomor,
                RIGHT(rp.no_reg, 3) AS angka,
                ps.nm_pasien,
                ps.no_tlp AS nohp,
                ps.no_rkm_medis,
                ps.no_peserta,
                ps.no_ktp,
                mpp.kd_poli_pcare AS kd_poli,
                pk.nm_poli,
                mdk.kd_dokter_pcare,
                d.nm_dokter,
                CONCAT(j.jam_mulai, '-', j.jam_selesai) AS jam_praktek,
                atb_latest.id AS antrean_bpjs_id,
                atb_latest.status_code AS last_status_code,
                atb_latest.message AS last_message
            FROM reg_periksa rp
            INNER JOIN poliklinik pk ON rp.kd_poli = pk.kd_poli
            INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            INNER JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
            INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            INNER JOIN jadwal j
                ON rp.kd_dokter = j.kd_dokter
                AND j.hari_kerja = ?
            INNER JOIN maping_dokter_pcare mdk ON d.kd_dokter = mdk.kd_dokter
            INNER JOIN maping_poliklinik_pcare mpp ON rp.kd_poli = mpp.kd_poli_rs
            LEFT JOIN (
                SELECT t1.id, t1.no_rawat, t1.status_code, t1.message
                FROM antrean_terkirim_bpjs t1
                INNER JOIN (
                    SELECT no_rawat, MAX(id) AS max_id
                    FROM antrean_terkirim_bpjs
                    GROUP BY no_rawat
                ) t2 ON t1.id = t2.max_id
            ) atb_latest ON rp.no_rawat = atb_latest.no_rawat
            WHERE mpp.kd_poli_pcare IN ('001', 'U0010', 'U0035', '003', '999')
              AND rp.kd_pj = 'bpj'
              AND rp.tgl_registrasi = ?
              AND (
                    atb_latest.id IS NULL
                    OR NOT (
                        (
                            COALESCE(atb_latest.status_code, 0) = 200
                            AND LOWER(TRIM(COALESCE(atb_latest.message, ''))) = 'ok'
                        )
                        OR (
                            COALESCE(atb_latest.status_code, 0) = 201
                            AND LOWER(TRIM(COALESCE(atb_latest.message, ''))) = 'peserta sudah terdaftar di poli tersebut pada hari ini'
                        )
                    )
                )
            GROUP BY rp.no_rawat
        ";

        $cari_pasien = queryPrepared($query, [
            $hari,
            $tanggal
        ]);

        if (empty($cari_pasien)) {
            writeLog("Tidak ada pasien BPJS baru hari ini.");
        } else {
            foreach ($cari_pasien as $data) {
                $lastStatusCode = $data['last_status_code'] ?? null;
                $lastMessage = $data['last_message'] ?? '';

                if (isTerminalBpjsStatus($lastStatusCode, $lastMessage)) {
                    writeLog("Lewati no_rawat {$data['no_rawat']} karena status BPJS sudah final: {$lastStatusCode} | {$lastMessage}");
                    continue;
                }

                if (isScreeningPendingMessage($lastMessage)) {
                    writeLog("No_rawat {$data['no_rawat']} masih menunggu skrining. Service akan mengirim ulang sampai status berubah.");
                }

                try {
                    nextQueueNumber($data['kd_poli'], $tanggal);
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

                    $timestamp = time();
                    $signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
                    $auth      = "Basic " . base64_encode("0373B006.pcare:LebihH1dup!:095");

                    $headers = [
                        "Content-Type: application/json",
                        "X-cons-id: $cons_id",
                        "X-timestamp: $timestamp",
                        "X-signature: $signature",
                        "user_key: $user_key",
                        "X-authorization: $auth"
                    ];

                    writeLog("PAYLOAD BPJS (RAW):");
                    writeLog(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    writeLog("======================================");

                    $ch = curl_init($url_add);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    $response = curl_exec($ch);

                    if ($response === false) {
                        writeLog("Curl error: " . curl_error($ch));
                        curl_close($ch);
                        continue;
                    }

                    curl_close($ch);

                    $decoded = json_decode($response, true);
                    $code = (int)($decoded['metadata']['code'] ?? 0);
                    $message = trim((string)($decoded['metadata']['message'] ?? 'Tidak ada pesan'));
                    $status = isTerminalBpjsStatus($code, $message) ? 'sukses' : 'proses ulang';

                    writeLog("Pasien dikirim: " . $data['nm_pasien'] . " => Status: $status ($code) | Pesan: $message");

                    if (!saveBpjsQueueResult($data, $response, $code, $message)) {
                        writeLog("Gagal menyimpan hasil BPJS untuk no_rawat {$data['no_rawat']}");
                    }
                } catch (Exception $e) {
                    writeLog("Error simpan pasien " . $data['nm_pasien'] . ": " . $e->getMessage());
                }

                sleep(1);
            }
        }

        sleep(5);
    } else {
        sleep(30);
    }
}
