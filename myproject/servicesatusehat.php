<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../function/function.php';
require_once __DIR__ . '/vendor/autoload.php';

function consoleSanitize($text)
{
    $replacements = [
        "\r\n" => "\n",
        "\r" => "\n",
        "❌" => "[ERROR]",
        "⚠️" => "[WARN]",
        "⚠" => "[WARN]",
        "ℹ️" => "[INFO]",
        "ℹ" => "[INFO]",
        "✅" => "[OK]",
        "🔹" => "[STEP]",
        "🔁" => "[RETRY]",
        "🆕" => "[NEW]",
        "🚀" => "[SEND]",
        "💾" => "[SAVE]",
        "🔍" => "[CHECK]",
        "👤" => "[PATIENT]",
        "—" => "-",
    ];

    $text = strtr($text, $replacements);
    $text = preg_replace("/[^\x09\x0A\x0D\x20-\x7E]/", "", $text);

    return $text;
}

function consolePrint($text = '')
{
    echo consoleSanitize($text) . PHP_EOL;
}

function consoleLine($char = '=', $length = 72)
{
    echo str_repeat($char, $length) . PHP_EOL;
}

function consoleSection($title)
{
    consoleLine('=');
    consolePrint('[' . date('Y-m-d H:i:s') . '] ' . $title);
    consoleLine('=');
}

function consolePatientHeader($name, $noRawat)
{
    consoleLine('-');
    consolePrint("[PATIENT] {$name}");
    consolePrint("[VISIT]   {$noRawat}");
    consoleLine('-');
}

if (PHP_SAPI === 'cli') {
    if (function_exists('sapi_windows_vt100_support')) {
        @sapi_windows_vt100_support(STDOUT, true);
    }
}

/* === CONFIG === */
$config = $conn->query("SELECT * FROM ss_config LIMIT 1")->fetch_assoc();
$client_id       = $config['client_id'];
$client_secret   = $config['client_secret'];
$organization_id = $config['organization_id'];
$location_id     = $config['location_id'];
$auth_url        = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken';
$fhir_url        = 'https://api-satusehat.kemkes.go.id/fhir-r4/v1/';
// $today           = date('Y-m-d');
// $today_1           = date('Y-m-d', strtotime('-1 day'));
$today_1           = date('2023-01-01');
$today_2           = date('Y-m-d');

/* === GET TOKEN === */
function getAccessToken($client_id, $client_secret, $auth_url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $auth_url . '?grant_type=client_credentials',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return $data['access_token'] ?? null;
}

/* === REQUEST HELPER === */
function getResource($type, $param, $token) {
    global $fhir_url;
    $url = $fhir_url . "$type?" . $param;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Accept: application/json"
        ]
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return $data['entry'][0]['resource']['id'] ?? null;
}

function createResource($type, $payload, $token) {
    global $fhir_url;
    $ch = curl_init($fhir_url . $type);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/fhir+json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return ($info['http_code'] == 201 && isset($data['id'])) ? $data['id'] : null;
}

function sendFHIR($type, $payload, $token) {
    global $fhir_url;
    $ch = curl_init($fhir_url . $type);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/fhir+json"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['http_code' => $info['http_code'], 'response' => json_decode($res, true)];
}

/* === LOOP UTAMA === */
consoleSection('Service SATUSEHAT berjalan');


while (true) {
    $token = getAccessToken($client_id, $client_secret, $auth_url);
    if (!$token) {
        consolePrint('[ERROR] Gagal ambil token, tunggu 60 detik...');
        sleep(60);
        continue;
    }

    $sql = "
        SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, p.no_ktp AS nik_pasien,
               d.nm_dokter, pg.no_ktp AS nik_dokter, rp.tgl_registrasi, rp.jam_reg,
               pl.nm_poli, rp.status_lanjut
        FROM reg_periksa rp
        LEFT JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis
        LEFT JOIN dokter d ON rp.kd_dokter=d.kd_dokter
        LEFT JOIN pegawai pg ON d.kd_dokter=pg.nik
        LEFT JOIN poliklinik pl ON rp.kd_poli=pl.kd_poli
        WHERE rp.tgl_registrasi BETWEEN '$today_1' AND '$today_2'
        ORDER BY rp.no_rawat ASC
    ";
    $result = $GLOBALS['conn']->query($sql);
    if ($result->num_rows === 0) {
        consolePrint('[INFO] Tidak ada encounter hari ini. Tunggu 30 detik...');
        sleep(30);
        continue;
    }

    while ($row = $result->fetch_assoc()) {
        echo PHP_EOL;
        consolePatientHeader($row['nm_pasien'], $row['no_rawat']);

        // CEK ENCOUNTER LOKAL
        $cek = $GLOBALS['conn']->query("SELECT * FROM satu_sehat_encounter_new WHERE no_rawat='{$row['no_rawat']}' AND status = 'success' LIMIT 1");
        if ($cek->num_rows > 0) {
            $enc = $cek->fetch_assoc();
            $id_encounter = $enc['id_encounter'];
            $patient_id   = $enc['patient_id'];
            echo "ℹ️ Encounter sudah ada." . PHP_EOL;
        } else {
            echo "🔹 Membuat Encounter baru..." . PHP_EOL;

            // === CEK / BUAT PATIENT ===
            $paramPatient = "name=" . urlencode($row['nm_pasien']) . "&identifier=https://fhir.kemkes.go.id/id/nik|" . $row['nik_pasien'];
            $patient_id = getResource("Patient", $paramPatient, $token);
            if (!$patient_id) {
                $payloadPatient = [
                    "resourceType" => "Patient",
                    "identifier" => [[
                        "system" => "https://fhir.kemkes.go.id/id/nik",
                        "value" => $row['nik_pasien']
                    ]],
                    "name" => [["use" => "official", "text" => $row['nm_pasien']]],
                    "active" => true
                ];
                $patient_id = createResource("Patient", $payloadPatient, $token);
            }

            // === CEK / BUAT PRACTITIONER ===
            $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|" . $row['nik_dokter'];
            $pract_id = getResource("Practitioner", $paramPract, $token);
            if (!$pract_id) {
                $payloadPract = [
                    "resourceType" => "Practitioner",
                    "identifier" => [[
                        "system" => "https://fhir.kemkes.go.id/id/nik",
                        "value" => $row['nik_dokter']
                    ]],
                    "name" => [["use" => "official", "text" => $row['nm_dokter']]],
                    "active" => true
                ];
                $pract_id = createResource("Practitioner", $payloadPract, $token);
            }

            if (!$patient_id || !$pract_id) {
                echo "⚠️ Gagal ambil/buat Patient atau Practitioner." . PHP_EOL;
                continue;
            }



            $startTime = date('Y-m-d\TH:i:sP', strtotime($row['tgl_registrasi'].' '.$row['jam_reg']));

            $payload = [
                "resourceType" => "Encounter",
                "status" => "arrived",
                "class" => [
                    "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code" => "AMB",
                    "display" => "ambulatory"
                ],
                "subject" => [
                    "reference" => "Patient/$patient_id",
                    "display" => $row['nm_pasien']
                ],
                "participant" => [[
                    "type" => [[
                        "coding" => [[
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                            "code" => "ATND",
                            "display" => "attender"
                        ]]
                    ]],
                    "individual" => [
                        "reference" => "Practitioner/$pract_id",
                        "display" => $row['nm_dokter']
                    ]
                ]],
                "period" => [
                    "start" => $startTime
                ],
                "statusHistory" => [[
                    "status" => "arrived",
                    "period" => ["start" => $startTime]
                ]],
                "location" => [[
                    "location" => [
                        "reference" => "Location/$location_id",
                        "display" => $row['nm_poli']
                    ]
                ]],
                "serviceProvider" => [
                    "reference" => "Organization/$organization_id"
                ],
                "identifier" => [[
                    "system" => "http://sys-ids.kemkes.go.id/encounter/$organization_id",
                    "value" => $row['no_rawat']
                ]]
            ];

            // Vardump payload sebelum dikirim
            // echo "===== PAYLOAD ENCOUNTER =====" . PHP_EOL;
            // var_dump($payload);

            $res = sendFHIR("Encounter", $payload, $token);

            // Vardump response dari server
            // echo "===== RESPONSE =====" . PHP_EOL;
            // var_dump($res);

            $status = ($res['http_code'] == 201) ? 'success' : 'failed';
            $id_encounter = $res['response']['id'] ?? null;
            $json = json_encode($res['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $stmt = $GLOBALS['conn']->prepare("
            INSERT INTO satu_sehat_encounter_new (no_rawat, id_encounter, patient_id, status, response)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                id_encounter = VALUES(id_encounter),
                patient_id = VALUES(patient_id),
                status = VALUES(status),
                response = VALUES(response)
        ");
        $stmt->bind_param("sssss", $row['no_rawat'], $id_encounter, $patient_id, $status, $json);
        $stmt->execute();


            echo ($status === 'success')
                ? "✅ Encounter berhasil dibuat!" . PHP_EOL
                : "⚠️ Encounter gagal dikirim ({$res['http_code']})" . PHP_EOL;
            }


        // === CEK & KIRIM CONDITION ===
        // $sqlDiag = "
        //     SELECT dp.kd_penyakit, p.nm_penyakit, rp.status_lanjut
        //     FROM diagnosa_pasien dp
        //     LEFT JOIN penyakit p ON dp.kd_penyakit = p.kd_penyakit
        //     LEFT JOIN reg_periksa rp ON dp.no_rawat = rp.no_rawat
        //     WHERE dp.no_rawat='{$row['no_rawat']}'
        // ";
        // $resDiag = $GLOBALS['conn']->query($sqlDiag);
        // if ($resDiag->num_rows === 0) {
        //     echo "ℹ️ Belum ada diagnosa." . PHP_EOL;
        //     continue;
        // }

        // while ($diag = $resDiag->fetch_assoc()) {
        //     $cekCond = $GLOBALS['conn']->query("SELECT * FROM satu_sehat_condition_new WHERE no_rawat='{$row['no_rawat']}' AND kd_penyakit='{$diag['kd_penyakit']}' LIMIT 1");
        //     if ($cekCond->num_rows > 0) {
        //         echo "ℹ️ Condition {$diag['kd_penyakit']} sudah ada." . PHP_EOL;
        //         continue;
        //     }

        //     $payloadCond = [
        //         "resourceType" => "Condition",
        //         "clinicalStatus" => ["coding" => [[
        //             "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
        //             "code" => "active",
        //             "display" => "Active"
        //         ]]],
        //         "category" => [[
        //             "coding" => [[
        //                 "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
        //                 "code" => "encounter-diagnosis",
        //                 "display" => "Encounter Diagnosis"
        //             ]]
        //         ]],
        //         "code" => [
        //             "coding" => [[
        //                 "system" => "http://hl7.org/fhir/sid/icd-10",
        //                 "code" => $diag['kd_penyakit'],
        //                 "display" => $diag['nm_penyakit']
        //             ]]
        //         ],
        //         "subject" => ["reference" => "Patient/$patient_id", "display" => $row['nm_pasien']],
        //         "encounter" => ["reference" => "Encounter/$id_encounter", "display" => "Kunjungan {$row['nm_pasien']}"]
        //     ];

 

        //     $resCond = sendFHIR("Condition", $payloadCond, $token);

         

        //     // Tentukan status dan ambil ID
        //     $statusCond = ($resCond['http_code'] == 201) ? 'berhasil' : 'gagal';
        //     $id_condition = $resCond['response']['id'] ?? null;
        //     $jsonCond = json_encode($resCond['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        //     // Optional: tampilkan status dan ID
        //     echo "Status Condition: $statusCond" . PHP_EOL;
        //     echo "ID Condition: " . ($id_condition ?? '-') . PHP_EOL;


        //     $stmtCond = $GLOBALS['conn']->prepare("
        //     INSERT INTO satu_sehat_condition_new (no_rawat, kd_penyakit, status, id_condition, response)
        //     VALUES (?, ?, ?, ?, ?)
        //     ON DUPLICATE KEY UPDATE
        //         kd_penyakit = VALUES(kd_penyakit),
        //         status = VALUES(status),
        //         id_condition = VALUES(id_condition),
        //         response = VALUES(response),
        //         updated_at = CURRENT_TIMESTAMP
        // ");

        // $stmtCond->bind_param(
        //     "sssss",
        //     $row['no_rawat'],
        //     $diag['kd_penyakit'],
        //     $statusCond,  
        //     $id_condition,
        //     $jsonCond
        // );

        // $stmtCond->execute();



        //     echo ($statusCond === 'berhasil')
        //         ? "✅ Condition {$diag['kd_penyakit']} terkirim untuk {$row['nm_pasien']}" . PHP_EOL
        //         : "⚠️ Condition {$diag['kd_penyakit']} gagal dikirim" . PHP_EOL;
        // }

                  // 🔍 Ambil semua diagnosa pasien berdasarkan no_rawat
$sqlDiag = "
    SELECT dp.kd_penyakit, p.nm_penyakit, rp.status_lanjut
    FROM diagnosa_pasien dp
    LEFT JOIN penyakit p ON dp.kd_penyakit = p.kd_penyakit
    LEFT JOIN reg_periksa rp ON dp.no_rawat = rp.no_rawat
    WHERE dp.no_rawat = ?
";
$stmtDiag = $GLOBALS['conn']->prepare($sqlDiag);
$stmtDiag->bind_param("s", $row['no_rawat']);
$stmtDiag->execute();
$resDiag = $stmtDiag->get_result();

if ($resDiag->num_rows === 0) {
    echo "ℹ️ Belum ada diagnosa untuk pasien {$row['nm_pasien']}." . PHP_EOL;
    continue;
}

while ($diag = $resDiag->fetch_assoc()) {

    // 🔎 Cek apakah Condition sudah ada dan ambil status terakhir
    $cekCond = $GLOBALS['conn']->prepare("
        SELECT status, id_condition 
        FROM satu_sehat_condition_new
        WHERE no_rawat = ? AND kd_penyakit = ?
        LIMIT 1
    ");
    $cekCond->bind_param("ss", $row['no_rawat'], $diag['kd_penyakit']);
    $cekCond->execute();
    $resCek = $cekCond->get_result();

    $shouldSend = false;
    $prevStatus = null;

    if ($resCek->num_rows > 0) {
        $dataCond = $resCek->fetch_assoc();
        $prevStatus = $dataCond['status'];

        if ($prevStatus === 'berhasil') {
            echo "✅ Condition {$diag['kd_penyakit']} sudah berhasil dikirim sebelumnya, dilewati." . PHP_EOL;
            continue;
        } else {
            echo "🔁 Condition {$diag['kd_penyakit']} sebelumnya gagal, akan dikirim ulang..." . PHP_EOL;
            $shouldSend = true;
        }
    } else {
        echo "🆕 Condition {$diag['kd_penyakit']} belum pernah dikirim, kirim baru..." . PHP_EOL;
        $shouldSend = true;
    }

    // Jika tidak perlu kirim, lanjut ke diagnosa berikutnya
    if (!$shouldSend) continue;

    // 🔧 Siapkan payload Condition
    $payloadCond = [
        "resourceType" => "Condition",
        "clinicalStatus" => ["coding" => [[
            "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
            "code" => "active",
            "display" => "Active"
        ]]],
        "category" => [[
            "coding" => [[
                "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                "code" => "encounter-diagnosis",
                "display" => "Encounter Diagnosis"
            ]]
        ]],
        "code" => [
            "coding" => [[
                "system" => "http://hl7.org/fhir/sid/icd-10",
                "code" => $diag['kd_penyakit'],
                "display" => $diag['nm_penyakit']
            ]]
        ],
        "subject" => [
            "reference" => "Patient/$patient_id",
            "display" => $row['nm_pasien']
        ],
        "encounter" => [
            "reference" => "Encounter/$id_encounter",
            "display" => "Kunjungan {$row['nm_pasien']}"
        ]
    ];

    // 🚀 Kirim ke FHIR API
    $resCond = sendFHIR("Condition", $payloadCond, $token);

    // 🧾 Ambil hasil response
    $statusCond = ($resCond['http_code'] == 201) ? 'berhasil' : 'gagal';
    $id_condition = $resCond['response']['id'] ?? null;
    $jsonCond = json_encode($resCond['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // 💾 Simpan ke DB (insert baru atau update kalau sudah ada)
    $sqlInsert = "
        INSERT INTO satu_sehat_condition_new
            (no_rawat, kd_penyakit, status, id_condition, response)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            id_condition = VALUES(id_condition),
            response = VALUES(response),
            updated_at = CURRENT_TIMESTAMP
    ";
    $stmtCond = $GLOBALS['conn']->prepare($sqlInsert);
    if (!$stmtCond) {
        die('❌ Gagal prepare query condition: ' . $GLOBALS['conn']->error);
    }

    $stmtCond->bind_param(
        "sssss",
        $row['no_rawat'],
        $diag['kd_penyakit'],
        $statusCond,
        $id_condition,
        $jsonCond
    );
    $stmtCond->execute();

    // 🧾 Log hasil
    // echo "------------------------------------" . PHP_EOL;
    echo "👤 Pasien: {$row['nm_pasien']}" . PHP_EOL;
    echo "🔹 Diagnosa: {$diag['kd_penyakit']} - {$diag['nm_penyakit']}" . PHP_EOL;
    echo ($statusCond === 'berhasil')
        ? "✅ Condition berhasil dikirim (ID: $id_condition)" . PHP_EOL
        : "⚠️ Condition gagal dikirim (akan dicoba lagi nanti)" . PHP_EOL;
    // echo "------------------------------------" . PHP_EOL;
}



            // === CEK & KIRIM OBSERVATION (SUHU TUBUH) ===
            $sqlSuhu = "
            SELECT 
                o.suhu_tubuh, 
                o.tgl_perawatan, 
                o.jam_rawat, 
                pg.no_ktp AS nik_dokter, 
                pg.nama AS nm_dokter, 
                suhu.status
            FROM pemeriksaan_ralan o
            LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
            LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
            LEFT JOIN satu_sehat_observationttvsuhu_new suhu ON o.no_rawat = suhu.no_rawat
            WHERE o.no_rawat = '{$row['no_rawat']}'
            LIMIT 1
        ";

        $resSuhu = $GLOBALS['conn']->query($sqlSuhu);

        if ($resSuhu && $resSuhu->num_rows > 0) {
            $suhu = $resSuhu->fetch_assoc();

            // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
            if (!empty($suhu['status']) && $suhu['status'] === 'berhasil') {
                echo "ℹ️ Observation suhu sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                goto lanjut_respirasi;
            }

            // === CEK APAKAH ADA NILAI suhu DI PEMERIKSAAN_RALAN ===
            if (!empty($suhu['suhu_tubuh'])) {
                $suhuVal = (int)$suhu['suhu_tubuh'];
                echo "✅ suhu ditemukan: {$suhuVal}" . PHP_EOL;

                $timeObs = date('Y-m-d\TH:i:sP', strtotime($suhu['tgl_perawatan'].' '.$suhu['jam_rawat']));
                $nik_dokter = trim($suhu['nik_dokter']);
                $nama_dokter = trim($suhu['nm_dokter']);

                // === AMBIL / BUAT PRACTITIONER ===
                if (!empty($nik_dokter)) {
                    $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                    $pract_id = getResource("Practitioner", $paramPract, $token);

                    if (!$pract_id) {
                        echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                        $payloadPract = [
                            "resourceType" => "Practitioner",
                            "identifier" => [[
                                "system" => "https://fhir.kemkes.go.id/id/nik",
                                "value" => $nik_dokter
                            ]],
                            "name" => [[
                                "use" => "official",
                                "text" => $nama_dokter
                            ]],
                            "active" => true
                        ];
                        $pract_id = createResource("Practitioner", $payloadPract, $token);
                    }
                } else {
                    echo "⚠️ NIK dokter tidak ditemukan, lewati suhu." . PHP_EOL;
                    goto lanjut_respirasi;
                }

                if (!$patient_id) {
                    echo "⚠️ Patient ID tidak ditemukan, lewati suhu." . PHP_EOL;
                    goto lanjut_respirasi;
                }

                // === BUAT PAYLOAD OBSERVATION suhu ===
                $payloadsuhu = [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [[
                        "coding" => [[
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs"
                        ]]
                    ]],
                    "code" => [
                        "coding" => [[
                            "system" => "http://loinc.org",
                            "code" => "8310-5",
                            "display" => "Body temperature"
                        ]]
                    ],
                    "subject" => ["reference" => "Patient/$patient_id"],
                    "performer" => [["reference" => "Practitioner/$pract_id"]],
                    "encounter" => [
                        "reference" => "Encounter/$id_encounter",
                        "display" => "Pemeriksaan suhu pasien {$row['nm_pasien']}"
                    ],
                    "effectiveDateTime" => $timeObs,
                    "issued" => $timeObs,
                    "valueQuantity" => [
                        "value" => $suhuVal,
                        "unit" => "degree Celsius",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "Cel"
                    ]
                ];

                // === KIRIM KE SATU SEHAT ===
                $resSuhuFHIR = sendFHIR("Observation", $payloadsuhu, $token);
                $statussuhu = ($resSuhuFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                $id_observation = $resSuhuFHIR['response']['id'] ?? null;
                $jsonsuhu = json_encode($resSuhuFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                // === SIMPAN KE DATABASE ===
                $stmtsuhu = $GLOBALS['conn']->prepare("
                INSERT INTO satu_sehat_observationttvsuhu_new
                    (no_rawat, suhu, status, observation_id, response_message, tanggal_kirim)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    suhu = VALUES(suhu),
                    status = VALUES(status),
                    observation_id = VALUES(observation_id),
                    response_message = VALUES(response_message),
                    tanggal_kirim = NOW(),
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmtsuhu->bind_param(
                "sdsss",
                $row['no_rawat'],
                $suhuVal,
                $statussuhu,
                $id_observation,
                $jsonsuhu
            );

            $stmtsuhu->execute();


                if ($statussuhu === 'berhasil') {
                    echo "✅ Observation suhu berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                } else {
                    echo "⚠️ Observation suhu gagal dikirim ({$resSuhuFHIR['http_code']})" . PHP_EOL;
                }

            } else {
                echo "ℹ️ Tidak ada data suhu untuk {$row['nm_pasien']}." . PHP_EOL;
            }
        } else {
            echo "ℹ️ Data suhu tidak ditemukan di database." . PHP_EOL;
        }

        // === LANJUT KE SPO2 ===
        lanjut_respirasi:

            // === KODE RESPIRASI DIBAWAH SINI ===
            // === CEK & KIRIM OBSERVATION (respirasi) ===
       
$sqlRespi = "
            SELECT 
                o.respirasi, 
                o.tgl_perawatan, 
                o.jam_rawat, 
                pg.no_ktp AS nik_dokter, 
                pg.nama AS nm_dokter, 
                respirasi.status
            FROM pemeriksaan_ralan o
            LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
            LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
            LEFT JOIN satu_sehat_observationttvrespirasi_new respirasi ON o.no_rawat = respirasi.no_rawat
            WHERE o.no_rawat = '{$row['no_rawat']}'
            LIMIT 1
        ";

        $resRespi = $GLOBALS['conn']->query($sqlRespi);

        if ($resRespi && $resRespi->num_rows > 0) {
            $respirasi = $resRespi->fetch_assoc();

            // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
            if (!empty($respirasi['status']) && $respirasi['status'] === 'berhasil') {
                echo "ℹ️ Observation respirasi sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                goto lanjut_nadi;
            }

            // === CEK APAKAH ADA NILAI respirasi DI PEMERIKSAAN_RALAN ===
            if (!empty($respirasi['respirasi'])) {
                $respirasiVal = (int)$respirasi['respirasi'];
                echo "✅ respirasi ditemukan: {$respirasiVal}" . PHP_EOL;

                $timeObs = date('Y-m-d\TH:i:sP', strtotime($respirasi['tgl_perawatan'].' '.$respirasi['jam_rawat']));
                $nik_dokter = trim($respirasi['nik_dokter']);
                $nama_dokter = trim($respirasi['nm_dokter']);

                // === AMBIL / BUAT PRACTITIONER ===
                if (!empty($nik_dokter)) {
                    $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                    $pract_id = getResource("Practitioner", $paramPract, $token);

                    if (!$pract_id) {
                        echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                        $payloadPract = [
                            "resourceType" => "Practitioner",
                            "identifier" => [[
                                "system" => "https://fhir.kemkes.go.id/id/nik",
                                "value" => $nik_dokter
                            ]],
                            "name" => [[
                                "use" => "official",
                                "text" => $nama_dokter
                            ]],
                            "active" => true
                        ];
                        $pract_id = createResource("Practitioner", $payloadPract, $token);
                    }
                } else {
                    echo "⚠️ NIK dokter tidak ditemukan, lewati respirasi." . PHP_EOL;
                    goto lanjut_nadi;
                }

                if (!$patient_id) {
                    echo "⚠️ Patient ID tidak ditemukan, lewati respirasi." . PHP_EOL;
                    goto lanjut_nadi;
                }

                // === BUAT PAYLOAD OBSERVATION respirasi ===
                $payloadrespirasi = [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [[
                        "coding" => [[
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs"
                        ]]
                    ]],
                    "code" => [
                        "coding" => [[
                            "system" => "http://loinc.org",
                            "code" => "9279-1",
                            "display" => "Respiratory rate"
                        ]]
                    ],
                    "subject" => ["reference" => "Patient/$patient_id"],
                    "performer" => [["reference" => "Practitioner/$pract_id"]],
                    "encounter" => [
                        "reference" => "Encounter/$id_encounter",
                        "display" => "Pemeriksaan respirasi pasien {$row['nm_pasien']}"
                    ],
                    "effectiveDateTime" => $timeObs,
                    "issued" => $timeObs,
                    "valueQuantity" => [
                        "value" => $respirasiVal,
                        "unit" => "breaths/minute",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "/min"
                    ]
                ];

                // === KIRIM KE SATU SEHAT ===
                $resRespiFHIR = sendFHIR("Observation", $payloadrespirasi, $token);
                $statusrespirasi = ($resRespiFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                $id_observation = $resRespiFHIR['response']['id'] ?? null;
                $jsonrespirasi = json_encode($resRespiFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                // === SIMPAN KE DATABASE ===
                $stmtrespirasi = $GLOBALS['conn']->prepare("
                INSERT INTO satu_sehat_observationttvrespirasi_new
                    (no_rawat, respirasi, status, observation_id, response_message, tanggal_kirim)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    respirasi = VALUES(respirasi),
                    status = VALUES(status),
                    observation_id = VALUES(observation_id),
                    response_message = VALUES(response_message),
                    tanggal_kirim = NOW(),
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmtrespirasi->bind_param(
                "sdsss",
                $row['no_rawat'],
                $respirasiVal,
                $statusrespirasi,
                $id_observation,
                $jsonrespirasi
            );

            $stmtrespirasi->execute();


                if ($statusrespirasi === 'berhasil') {
                    echo "✅ Observation respirasi berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                } else {
                    echo "⚠️ Observation respirasi gagal dikirim ({$resRespiFHIR['http_code']})" . PHP_EOL;
                }

            } else {
                echo "ℹ️ Tidak ada data respirasi untuk {$row['nm_pasien']}." . PHP_EOL;
            }
        } else {
            echo "ℹ️ Data respirasi tidak ditemukan di database." . PHP_EOL;
        }

     
        lanjut_nadi:



            // === NADI ===
            $sqlNadi = "
                SELECT 
                    o.nadi, 
                    o.tgl_perawatan, 
                    o.jam_rawat, 
                    pg.no_ktp AS nik_dokter, 
                    pg.nama AS nm_dokter, 
                    nadi.status
                FROM pemeriksaan_ralan o
                LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
                LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
                LEFT JOIN satu_sehat_observationttvnadi_new nadi ON o.no_rawat = nadi.no_rawat
                WHERE o.no_rawat = '{$row['no_rawat']}'
                LIMIT 1
            ";

            $resNadi = $GLOBALS['conn']->query($sqlNadi);

            if ($resNadi && $resNadi->num_rows > 0) {
                $nadi = $resNadi->fetch_assoc();

                // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
                if (!empty($nadi['status']) && $nadi['status'] === 'berhasil') {
                    echo "ℹ️ Observation Nadi sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_spo2;
                }

                // === CEK APAKAH ADA NILAI NADI DI PEMERIKSAAN_RALAN ===
                if (!empty($nadi['nadi'])) {
                    $nadiVal = (int)$nadi['nadi'];
                    echo "✅ Nadi ditemukan: {$nadiVal}" . PHP_EOL;

                    $timeObs = date('Y-m-d\TH:i:sP', strtotime($nadi['tgl_perawatan'].' '.$nadi['jam_rawat']));
                    $nik_dokter = trim($nadi['nik_dokter']);
                    $nama_dokter = trim($nadi['nm_dokter']);

                    // === AMBIL / BUAT PRACTITIONER ===
                    if (!empty($nik_dokter)) {
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                        $pract_id = getResource("Practitioner", $paramPract, $token);

                        if (!$pract_id) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $nik_dokter
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $nama_dokter
                                ]],
                                "active" => true
                            ];
                            $pract_id = createResource("Practitioner", $payloadPract, $token);
                        }
                    } else {
                        echo "⚠️ NIK dokter tidak ditemukan, lewati nadi." . PHP_EOL;
                        goto lanjut_spo2;
                    }

                    if (!$patient_id) {
                        echo "⚠️ Patient ID tidak ditemukan, lewati nadi." . PHP_EOL;
                        goto lanjut_spo2;
                    }

                    // === BUAT PAYLOAD OBSERVATION NADI ===
                    $payloadNadi = [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [[
                            "coding" => [[
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]]
                        ]],
                        "code" => [
                            "coding" => [[
                                "system" => "http://loinc.org",
                                "code" => "8867-4",
                                "display" => "Heart rate"
                            ]]
                        ],
                        "subject" => ["reference" => "Patient/$patient_id"],
                        "performer" => [["reference" => "Practitioner/$pract_id"]],
                        "encounter" => [
                            "reference" => "Encounter/$id_encounter",
                            "display" => "Pemeriksaan nadi pasien {$row['nm_pasien']}"
                        ],
                        "effectiveDateTime" => $timeObs,
                        "issued" => $timeObs,
                        "valueQuantity" => [
                            "value" => $nadiVal,
                            "unit" => "beats/minute",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "/min"
                        ]
                    ];

                    // === KIRIM KE SATU SEHAT ===
                    $resNadiFHIR = sendFHIR("Observation", $payloadNadi, $token);
                    $statusNadi = ($resNadiFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                    $id_observation = $resNadiFHIR['response']['id'] ?? null;
                    $jsonNadi = json_encode($resNadiFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // === SIMPAN KE DATABASE ===
                    $stmtNadi = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_observationttvnadi_new
                        (no_rawat, nadi, status, observation_id, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        nadi = VALUES(nadi),
                        status = VALUES(status),
                        observation_id = VALUES(observation_id),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmtNadi->bind_param(
                    "sdsss",
                    $row['no_rawat'],
                    $nadiVal,
                    $statusNadi,
                    $id_observation,
                    $jsonNadi
                );

                $stmtNadi->execute();


                    if ($statusNadi === 'berhasil') {
                        echo "✅ Observation Nadi berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                    } else {
                        echo "⚠️ Observation Nadi gagal dikirim ({$resNadiFHIR['http_code']})" . PHP_EOL;
                    }

                } else {
                    echo "ℹ️ Tidak ada data nadi untuk {$row['nm_pasien']}." . PHP_EOL;
                }
            } else {
                echo "ℹ️ Data nadi tidak ditemukan di database." . PHP_EOL;
            }

            // === LANJUT KE SPO2 ===
            lanjut_spo2:


            //spo2
            $sqlSpo2 = "
                SELECT 
                    o.spo2, 
                    o.tgl_perawatan, 
                    o.jam_rawat, 
                    pg.no_ktp AS nik_dokter, 
                    pg.nama AS nm_dokter, 
                    spo2.status
                FROM pemeriksaan_ralan o
                LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
                LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
                LEFT JOIN satu_sehat_observationttvspo2_new spo2 ON o.no_rawat = spo2.no_rawat
                WHERE o.no_rawat = '{$row['no_rawat']}'
                LIMIT 1
            ";

            $resspo2 = $GLOBALS['conn']->query($sqlSpo2);

            if ($resspo2 && $resspo2->num_rows > 0) {
                $spo2 = $resspo2->fetch_assoc();

                // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
                if (!empty($spo2['status']) && $spo2['status'] === 'berhasil') {
                    echo "ℹ️ Observation spo2 sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_gcs;
                }

                // === CEK APAKAH ADA NILAI spo2 DI PEMERIKSAAN_RALAN ===
                if (!empty($spo2['spo2'])) {
                    $spo2Val = (int)$spo2['spo2'];
                    echo "✅ spo2 ditemukan: {$spo2Val}" . PHP_EOL;

                    $timeObs = date('Y-m-d\TH:i:sP', strtotime($spo2['tgl_perawatan'].' '.$spo2['jam_rawat']));
                    $nik_dokter = trim($spo2['nik_dokter']);
                    $nama_dokter = trim($spo2['nm_dokter']);

                    // === AMBIL / BUAT PRACTITIONER ===
                    if (!empty($nik_dokter)) {
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                        $pract_id = getResource("Practitioner", $paramPract, $token);

                        if (!$pract_id) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $nik_dokter
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $nama_dokter
                                ]],
                                "active" => true
                            ];
                            $pract_id = createResource("Practitioner", $payloadPract, $token);
                        }
                    } else {
                        echo "⚠️ NIK dokter tidak ditemukan, lewati spo2." . PHP_EOL;
                        goto lanjut_gcs;
                    }

                    if (!$patient_id) {
                        echo "⚠️ Patient ID tidak ditemukan, lewati spo2." . PHP_EOL;
                        goto lanjut_gcs;
                    }

                    // === BUAT PAYLOAD OBSERVATION spo2 ===
                    $payloadspo2 = [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [[
                            "coding" => [[
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]]
                        ]],
                        "code" => [
                            "coding" => [[
                                "system" => "http://loinc.org",
                                "code" => "59408-5",
                                "display" => "Oxygen saturation"
                            ]]
                        ],
                        "subject" => ["reference" => "Patient/$patient_id"],
                        "performer" => [["reference" => "Practitioner/$pract_id"]],
                        "encounter" => [
                            "reference" => "Encounter/$id_encounter",
                            "display" => "Pemeriksaan spo2 pasien {$row['nm_pasien']}"
                        ],
                        "effectiveDateTime" => $timeObs,
                        "issued" => $timeObs,
                        "valueQuantity" => [
                            "value" => $spo2Val,
                            "unit" => "percent saturation",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "%"
                        ]
                    ];

                    // === KIRIM KE SATU SEHAT ===
                    $resspo2FHIR = sendFHIR("Observation", $payloadspo2, $token);
                    $statusspo2 = ($resspo2FHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                    $id_observation = $resspo2FHIR['response']['id'] ?? null;
                    $jsonspo2 = json_encode($resspo2FHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // === SIMPAN KE DATABASE ===
                    $stmtspo2 = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_observationttvspo2_new
                        (no_rawat, spo2, status, observation_id, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        spo2 = VALUES(spo2),
                        status = VALUES(status),
                        observation_id = VALUES(observation_id),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmtspo2->bind_param(
                    "sdsss",
                    $row['no_rawat'],
                    $spo2Val,
                    $statusspo2,
                    $id_observation,
                    $jsonspo2
                );

                $stmtspo2->execute();

                    if ($statusspo2 === 'berhasil') {
                        echo "✅ Observation spo2 berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                    } else {
                        echo "⚠️ Observation spo2 gagal dikirim ({$resspo2FHIR['http_code']})" . PHP_EOL;
                    }

                } else {
                    echo "ℹ️ Tidak ada data spo2 untuk {$row['nm_pasien']}." . PHP_EOL;
                }
            } else {
                echo "ℹ️ Data spo2 tidak ditemukan di database." . PHP_EOL;
            }

            // === LANJUT KE SPO2 ===
            lanjut_gcs:


            //gcs
            $sqlGcs = "
                SELECT 
                    o.gcs, 
                    o.tgl_perawatan, 
                    o.jam_rawat, 
                    pg.no_ktp AS nik_dokter, 
                    pg.nama AS nm_dokter, 
                    gcs.status
                FROM pemeriksaan_ralan o
                LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
                LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
                LEFT JOIN satu_sehat_observationttvgcs_new gcs ON o.no_rawat = gcs.no_rawat
                WHERE o.no_rawat = '{$row['no_rawat']}'
                LIMIT 1
            ";

            $resgcs = $GLOBALS['conn']->query($sqlGcs);

            if ($resgcs && $resgcs->num_rows > 0) {
                $gcs = $resgcs->fetch_assoc();

                // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
                if (!empty($gcs['status']) && $gcs['status'] === 'berhasil') {
                    echo "ℹ️ Observation gcs sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_kesadaran;
                }

                // === CEK APAKAH ADA NILAI gcs DI PEMERIKSAAN_RALAN ===
                if (!empty($gcs['gcs'])) {
                    $gcsVal = (int)$gcs['gcs'];
                    echo "✅ gcs ditemukan: {$gcsVal}" . PHP_EOL;

                    $timeObs = date('Y-m-d\TH:i:sP', strtotime($gcs['tgl_perawatan'].' '.$gcs['jam_rawat']));
                    $nik_dokter = trim($gcs['nik_dokter']);
                    $nama_dokter = trim($gcs['nm_dokter']);

                    // === AMBIL / BUAT PRACTITIONER ===
                    if (!empty($nik_dokter)) {
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                        $pract_id = getResource("Practitioner", $paramPract, $token);

                        if (!$pract_id) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $nik_dokter
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $nama_dokter
                                ]],
                                "active" => true
                            ];
                            $pract_id = createResource("Practitioner", $payloadPract, $token);
                        }
                    } else {
                        echo "⚠️ NIK dokter tidak ditemukan, lewati gcs." . PHP_EOL;
                        goto lanjut_kesadaran;
                    }

                    if (!$patient_id) {
                        echo "⚠️ Patient ID tidak ditemukan, lewati gcs." . PHP_EOL;
                        goto lanjut_kesadaran;
                    }

                    // === BUAT PAYLOAD OBSERVATION gcs ===
                    $payloadgcs = [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [[
                            "coding" => [[
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]]
                        ]],
                        "code" => [
                            "coding" => [[
                                "system" => "http://loinc.org",
                                "code" => "9269-2",
                                "display" => "Glasgow coma score total"
                            ]]
                        ],
                        "subject" => ["reference" => "Patient/$patient_id"],
                        "performer" => [["reference" => "Practitioner/$pract_id"]],
                        "encounter" => [
                            "reference" => "Encounter/$id_encounter",
                            "display" => "Pemeriksaan gcs pasien {$row['nm_pasien']}"
                        ],
                        "effectiveDateTime" => $timeObs,
                        "issued" => $timeObs,
                        "valueQuantity" => [
                            "value" => $gcsVal,
                            "system" => "http://unitsofmeasure.org",
                            "code" => "{score}"
                        ]
                    ];

                    // var_dump($payloadgcs);
                    // exit();

                    // === KIRIM KE SATU SEHAT ===
                    $resgcsFHIR = sendFHIR("Observation", $payloadgcs, $token);
                    $statusgcs = ($resgcsFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                    $id_observation = $resgcsFHIR['response']['id'] ?? null;
                    $jsongcs = json_encode($resgcsFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // === SIMPAN KE DATABASE ===
                    $stmtgcs = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_observationttvgcs_new
                        (no_rawat, gcs, status, observation_id, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        gcs = VALUES(gcs),
                        status = VALUES(status),
                        observation_id = VALUES(observation_id),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmtgcs->bind_param(
                    "sdsss",
                    $row['no_rawat'],
                    $gcsVal,
                    $statusgcs,
                    $id_observation,
                    $jsongcs
                );

                $stmtgcs->execute();


                    if ($statusgcs === 'berhasil') {
                        echo "✅ Observation gcs berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                    } else {
                        echo "⚠️ Observation gcs gagal dikirim ({$resgcsFHIR['http_code']})" . PHP_EOL;
                    }

                } else {
                    echo "ℹ️ Tidak ada data gcs untuk {$row['nm_pasien']}." . PHP_EOL;
                }
            } else {
                echo "ℹ️ Data gcs tidak ditemukan di database." . PHP_EOL;
            }

            // === LANJUT KE gcs ===
            lanjut_kesadaran:



            //kesadaran
            $sqlKesadaran = "
                SELECT 
                    o.kesadaran, 
                    o.tgl_perawatan, 
                    o.jam_rawat, 
                    pg.no_ktp AS nik_dokter, 
                    pg.nama AS nm_dokter, 
                    kesadaran.status
                FROM pemeriksaan_ralan o
                LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
                LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
                LEFT JOIN satu_sehat_observationttvkesadaran_new kesadaran ON o.no_rawat = kesadaran.no_rawat
                WHERE o.no_rawat = '{$row['no_rawat']}'
                LIMIT 1
            ";

            $reskesadaran = $GLOBALS['conn']->query($sqlKesadaran);

            if ($reskesadaran && $reskesadaran->num_rows > 0) {
                $kesadaran = $reskesadaran->fetch_assoc();

                // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
                if (!empty($kesadaran['status']) && $kesadaran['status'] === 'berhasil') {
                    echo "ℹ️ Observation kesadaran sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_tensi;
                }

                // === CEK APAKAH ADA NILAI kesadaran DI PEMERIKSAAN_RALAN ===
                if (!empty($kesadaran['kesadaran'])) {
                    $kesadaranVal = trim($kesadaran['kesadaran']); // ambil string bersih tanpa spasi tambahan
                    echo "✅ kesadaran ditemukan: {$kesadaranVal}" . PHP_EOL;

                    // var_dump($kesadaranVal);
                    // exit();

                    $timeObs = date('Y-m-d\TH:i:sP', strtotime($kesadaran['tgl_perawatan'].' '.$kesadaran['jam_rawat']));
                    $nik_dokter = trim($kesadaran['nik_dokter']);
                    $nama_dokter = trim($kesadaran['nm_dokter']);

                    // === AMBIL / BUAT PRACTITIONER ===
                    if (!empty($nik_dokter)) {
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                        $pract_id = getResource("Practitioner", $paramPract, $token);

                        if (!$pract_id) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $nik_dokter
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $nama_dokter
                                ]],
                                "active" => true
                            ];
                            $pract_id = createResource("Practitioner", $payloadPract, $token);
                        }
                    } else {
                        echo "⚠️ NIK dokter tidak ditemukan, lewati kesadaran." . PHP_EOL;
                        goto lanjut_tensi;
                    }

                    if (!$patient_id) {
                        echo "⚠️ Patient ID tidak ditemukan, lewati kesadaran." . PHP_EOL;
                        goto lanjut_tensi;
                    }


                    $kesadaranValDB = trim($kesadaran['kesadaran']); // misal: "Compos Mentis"

                    $mappingKesadaran = [
                    "Compos Mentis" => "Alert",
                    "Somnolence"    => "Voice",
                    "Sopor"         => "Pain",
                    "Coma"          => "Unresponsive"
                ];

                $kesadaranVal = isset($mappingKesadaran[$kesadaranValDB]) 
                                 ? $mappingKesadaran[$kesadaranValDB] 
                                 : $kesadaranValDB;

                    // === BUAT PAYLOAD OBSERVATION kesadaran ===
                    $payloadkesadaran = [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [[
                            "coding" => [[
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "exam",
                                "display" => "Exam"
                            ]]
                        ]],
                        "code" => [
                            "coding" => [[
                                "system" => "http://snomed.info/sct",
                                "code" => "1104441000000107",
                                "display" => "ACVPU (Alert Confusion Voice Pain Unresponsive) scale score"
                            ]]
                        ],
                        "subject" => ["reference" => "Patient/$patient_id"],
                        "performer" => [["reference" => "Practitioner/$pract_id"]],
                        "encounter" => [
                            "reference" => "Encounter/$id_encounter",
                            "display" => "Pemeriksaan Fisik Kesadaran di {$row['nm_pasien']}"
                        ],
                        "effectiveDateTime" => $timeObs,
                        "issued" => $timeObs,
                        "valueCodeableConcept" => [
                            "text" => $kesadaranVal
                        ]
                    ];


                    // === KIRIM KE SATU SEHAT ===
                    $reskesadaranFHIR = sendFHIR("Observation", $payloadkesadaran, $token);
                    $statuskesadaran = ($reskesadaranFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                    $id_observation = $reskesadaranFHIR['response']['id'] ?? null;
                    $jsonkesadaran = json_encode($reskesadaranFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // === SIMPAN KE DATABASE ===
                    $stmtkesadaran = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_observationttvkesadaran_new
                        (no_rawat, kesadaran, status, observation_id, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        kesadaran = VALUES(kesadaran),
                        status = VALUES(status),
                        observation_id = VALUES(observation_id),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmtkesadaran->bind_param(
                    "sdsss",
                    $row['no_rawat'],
                    $kesadaranVal,
                    $statuskesadaran,
                    $id_observation,
                    $jsonkesadaran
                );

                $stmtkesadaran->execute();


                    if ($statuskesadaran === 'berhasil') {
                        echo "✅ Observation kesadaran berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                    } else {
                        echo "⚠️ Observation kesadaran gagal dikirim ({$reskesadaranFHIR['http_code']})" . PHP_EOL;
                    }

                } else {
                    echo "ℹ️ Tidak ada data kesadaran untuk {$row['nm_pasien']}." . PHP_EOL;
                }
            } else {
                echo "ℹ️ Data kesadaran tidak ditemukan di database." . PHP_EOL;
            }

            // === LANJUT KE tensi ===
            lanjut_tensi:



            //tensi
            $sqlTensi = "
            SELECT 
                o.tensi, 
                o.tgl_perawatan, 
                o.jam_rawat, 
                pg.no_ktp AS nik_dokter, 
                pg.nama AS nm_dokter, 
                tensi.status
            FROM pemeriksaan_ralan o
            LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
            LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
            LEFT JOIN satu_sehat_observationttvtensi_new tensi ON o.no_rawat = tensi.no_rawat
            WHERE o.no_rawat = '{$row['no_rawat']}'
            LIMIT 1
        ";

        $restensi = $GLOBALS['conn']->query($sqlTensi);

        if ($restensi && $restensi->num_rows > 0) {
            $tensi = $restensi->fetch_assoc();

            // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
            if (!empty($tensi['status']) && $tensi['status'] === 'berhasil') {
                echo "ℹ️ Observation tensi sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                goto lanjut_tinggi;
            }

            // === CEK APAKAH ADA NILAI tensi DI PEMERIKSAAN_RALAN ===
            if (!empty($tensi['tensi'])) {

                // === Pisahkan nilai sistole dan diastole ===
                $tensiRaw = trim($tensi['tensi']);
                $sistole = null;
                $diastole = null;

                if (strpos($tensiRaw, '/') !== false) {
                    list($sistole, $diastole) = explode('/', $tensiRaw);
                    $sistole = floatval(trim($sistole));
                    $diastole = floatval(trim($diastole));
                } else {
                    // Jika tidak ada tanda '/', asumsikan hanya sistole
                    $sistole = floatval($tensiRaw);
                    $diastole = null;
                }

                // var_dump($sistole);
                // var_dump($diastole);
                // exit;

                echo "✅ Tensi ditemukan: {$sistole}/{$diastole}" . PHP_EOL;

                // === Format waktu pengukuran ===
                $timeObs = date('Y-m-d\TH:i:sP', strtotime($tensi['tgl_perawatan'].' '.$tensi['jam_rawat']));
                $nik_dokter = trim($tensi['nik_dokter']);
                $nama_dokter = trim($tensi['nm_dokter']);

                // === AMBIL / BUAT PRACTITIONER ===
                if (!empty($nik_dokter)) {
                    $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                    $pract_id = getResource("Practitioner", $paramPract, $token);

                    if (!$pract_id) {
                        echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                        $payloadPract = [
                            "resourceType" => "Practitioner",
                            "identifier" => [[
                                "system" => "https://fhir.kemkes.go.id/id/nik",
                                "value" => $nik_dokter
                            ]],
                            "name" => [[
                                "use" => "official",
                                "text" => $nama_dokter
                            ]],
                            "active" => true
                        ];
                        $pract_id = createResource("Practitioner", $payloadPract, $token);
                    }
                } else {
                    echo "⚠️ NIK dokter tidak ditemukan, lewati tensi." . PHP_EOL;
                    goto lanjut_tinggi;
                }

                if (!$patient_id) {
                    echo "⚠️ Patient ID tidak ditemukan, lewati tensi." . PHP_EOL;
                    goto lanjut_tinggi;
                }

                // === BUAT PAYLOAD OBSERVATION TENSI (sistol/diastol) ===
                $payloadtensi = [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [[
                        "coding" => [[
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs"
                        ]]
                    ]],
                    "code" => [
                        "coding" => [[
                            "system" => "http://loinc.org",
                            "code" => "35094-2",
                            "display" => "Blood pressure panel"
                        ]],
                        "text" => "Blood pressure systolic & diastolic"
                    ],
                    "subject" => ["reference" => "Patient/$patient_id"],
                    "performer" => [["reference" => "Practitioner/$pract_id"]],
                    "encounter" => [
                        "reference" => "Encounter/$id_encounter",
                        "display" => "Pemeriksaan fisik tensi pasien {$row['nm_pasien']}"
                    ],
                    "effectiveDateTime" => $timeObs,
                    "component" => [
                        [
                            "code" => [
                                "coding" => [[
                                    "system" => "http://loinc.org",
                                    "code" => "8480-6",
                                    "display" => "Systolic blood pressure"
                                ]]
                            ],
                            "valueQuantity" => [
                                "value" => $sistole,
                                "unit" => "mmHg",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "mm[Hg]"
                            ]
                        ],
                        [
                            "code" => [
                                "coding" => [[
                                    "system" => "http://loinc.org",
                                    "code" => "8462-4",
                                    "display" => "Diastolic blood pressure"
                                ]]
                            ],
                            "valueQuantity" => [
                                "value" => $diastole,
                                "unit" => "mmHg",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "mm[Hg]"
                            ]
                        ]
                    ]
                ];

                // === KIRIM KE SATU SEHAT ===
                $restensiFHIR = sendFHIR("Observation", $payloadtensi, $token);
                $statustensi = ($restensiFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                $id_observation = $restensiFHIR['response']['id'] ?? null;
                $jsontensi = json_encode($restensiFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                // === SIMPAN KE DATABASE ===
                $stmttensi = $GLOBALS['conn']->prepare("
                INSERT INTO satu_sehat_observationttvtensi_new
                    (no_rawat, sistole, diastole, status, observation_id, response_message, tanggal_kirim)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    sistole = VALUES(sistole),
                    diastole = VALUES(diastole),
                    status = VALUES(status),
                    observation_id = VALUES(observation_id),
                    response_message = VALUES(response_message),
                    tanggal_kirim = NOW(),
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmttensi->bind_param(
                "sddsss",
                $row['no_rawat'],
                $sistole,
                $diastole,
                $statustensi,
                $id_observation,
                $jsontensi
            );

            $stmttensi->execute();


                if ($statustensi === 'berhasil') {
                    echo "✅ Observation Blood Pressure (sistol/diastol) berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                } else {
                    echo "⚠️ Observation Blood Pressure gagal dikirim ({$restensiFHIR['http_code']})" . PHP_EOL;
                }

            } else {
                echo "ℹ️ Tidak ada data tensi untuk {$row['nm_pasien']}." . PHP_EOL;
            }
        } else {
            echo "ℹ️ Data tensi tidak ditemukan di database." . PHP_EOL;
        }

        // === LANJUT KE tensi ===
        lanjut_tinggi:

        //tinggi
        $sqlTinggi = "
                SELECT 
                    o.tinggi, 
                    o.tgl_perawatan, 
                    o.jam_rawat, 
                    pg.no_ktp AS nik_dokter, 
                    pg.nama AS nm_dokter, 
                    tinggi.status
                FROM pemeriksaan_ralan o
                LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
                LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
                LEFT JOIN satu_sehat_observationttvtinggi_new tinggi ON o.no_rawat = tinggi.no_rawat
                WHERE o.no_rawat = '{$row['no_rawat']}'
                LIMIT 1
            ";

            $restinggi = $GLOBALS['conn']->query($sqlTinggi);

            if ($restinggi && $restinggi->num_rows > 0) {
                $tinggi = $restinggi->fetch_assoc();

                // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
                if (!empty($tinggi['status']) && $tinggi['status'] === 'berhasil') {
                    echo "ℹ️ Observation tinggi sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_berat;
                }

                // === CEK APAKAH ADA NILAI tinggi DI PEMERIKSAAN_RALAN ===
                if (!empty($tinggi['tinggi'])) {
                    $tinggiVal = (int)$tinggi['tinggi'];
                    echo "✅ tinggi ditemukan: {$tinggiVal}" . PHP_EOL;

                    $timeObs = date('Y-m-d\TH:i:sP', strtotime($tinggi['tgl_perawatan'].' '.$tinggi['jam_rawat']));
                    $nik_dokter = trim($tinggi['nik_dokter']);
                    $nama_dokter = trim($tinggi['nm_dokter']);

                    // === AMBIL / BUAT PRACTITIONER ===
                    if (!empty($nik_dokter)) {
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                        $pract_id = getResource("Practitioner", $paramPract, $token);

                        if (!$pract_id) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $nik_dokter
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $nama_dokter
                                ]],
                                "active" => true
                            ];
                            $pract_id = createResource("Practitioner", $payloadPract, $token);
                        }
                    } else {
                        echo "⚠️ NIK dokter tidak ditemukan, lewati tinggi." . PHP_EOL;
                        goto lanjut_berat;
                    }

                    if (!$patient_id) {
                        echo "⚠️ Patient ID tidak ditemukan, lewati tinggi." . PHP_EOL;
                        goto lanjut_berat;
                    }

                    // === BUAT PAYLOAD OBSERVATION tinggi ===
                    $payloadtinggi = [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [[
                            "coding" => [[
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]]
                        ]],
                        "code" => [
                            "coding" => [[
                                "system" => "http://loinc.org",
                                "code" => "8302-2",
                                "display" => "Glasgow coma score total"
                            ]]
                        ],
                        "subject" => ["reference" => "Patient/$patient_id"],
                        "performer" => [["reference" => "Practitioner/$pract_id"]],
                        "encounter" => [
                            "reference" => "Encounter/$id_encounter",
                            "display" => "Pemeriksaan tinggi pasien {$row['nm_pasien']}"
                        ],
                        "effectiveDateTime" => $timeObs,
                        "issued" => $timeObs,
                        "valueQuantity" => [
                            "value" => $tinggiVal,
                            "unit" => "centimeter",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "cm"
                        ]
                    ];

                    // var_dump($payloadtinggi);
                    // exit();

                    // === KIRIM KE SATU SEHAT ===
                    $restinggiFHIR = sendFHIR("Observation", $payloadtinggi, $token);
                    $statustinggi = ($restinggiFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                    $id_observation = $restinggiFHIR['response']['id'] ?? null;
                    $jsontinggi = json_encode($restinggiFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // === SIMPAN KE DATABASE ===
                    $stmttinggi = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_observationttvtinggi_new
                        (no_rawat, tinggi, status, observation_id, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        tinggi = VALUES(tinggi),
                        status = VALUES(status),
                        observation_id = VALUES(observation_id),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmttinggi->bind_param(
                    "sdsss",
                    $row['no_rawat'],
                    $tinggiVal,
                    $statustinggi,
                    $id_observation,
                    $jsontinggi
                );

                $stmttinggi->execute();


                    if ($statustinggi === 'berhasil') {
                        echo "✅ Observation tinggi berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                    } else {
                        echo "⚠️ Observation tinggi gagal dikirim ({$restinggiFHIR['http_code']})" . PHP_EOL;
                    }

                } else {
                    echo "ℹ️ Tidak ada data tinggi untuk {$row['nm_pasien']}." . PHP_EOL;
                }
            } else {
                echo "ℹ️ Data tinggi tidak ditemukan di database." . PHP_EOL;
            }

            // === LANJUT KE berat ===
            lanjut_berat:


            //berat
            $sqlBerat = "
                SELECT 
                    o.berat, 
                    o.tgl_perawatan, 
                    o.jam_rawat, 
                    pg.no_ktp AS nik_dokter, 
                    pg.nama AS nm_dokter, 
                    berat.status
                FROM pemeriksaan_ralan o
                LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
                LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
                LEFT JOIN satu_sehat_observationttvberat_new berat ON o.no_rawat = berat.no_rawat
                WHERE o.no_rawat = '{$row['no_rawat']}'
                LIMIT 1
            ";

            $resberat = $GLOBALS['conn']->query($sqlBerat);

            if ($resberat && $resberat->num_rows > 0) {
                $berat = $resberat->fetch_assoc();

                // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
                if (!empty($berat['status']) && $berat['status'] === 'berhasil') {
                    echo "ℹ️ Observation berat sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_lingkarperut;
                }

                // === CEK APAKAH ADA NILAI berat DI PEMERIKSAAN_RALAN ===
                if (!empty($berat['berat'])) {
                    $beratVal = (int)$berat['berat'];
                    echo "✅ berat ditemukan: {$beratVal}" . PHP_EOL;

                    $timeObs = date('Y-m-d\TH:i:sP', strtotime($berat['tgl_perawatan'].' '.$berat['jam_rawat']));
                    $nik_dokter = trim($berat['nik_dokter']);
                    $nama_dokter = trim($berat['nm_dokter']);

                    // === AMBIL / BUAT PRACTITIONER ===
                    if (!empty($nik_dokter)) {
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                        $pract_id = getResource("Practitioner", $paramPract, $token);

                        if (!$pract_id) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $nik_dokter
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $nama_dokter
                                ]],
                                "active" => true
                            ];
                            $pract_id = createResource("Practitioner", $payloadPract, $token);
                        }
                    } else {
                        echo "⚠️ NIK dokter tidak ditemukan, lewati berat." . PHP_EOL;
                        goto lanjut_lingkarperut;
                    }

                    if (!$patient_id) {
                        echo "⚠️ Patient ID tidak ditemukan, lewati berat." . PHP_EOL;
                        goto lanjut_lingkarperut;
                    }

                    // === BUAT PAYLOAD OBSERVATION berat ===
                    $payloadberat = [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [[
                            "coding" => [[
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]]
                        ]],
                        "code" => [
                            "coding" => [[
                                "system" => "http://loinc.org",
                                "code" => "29463-7",
                                "display" => "Body Weight"
                            ]]
                        ],
                        "subject" => ["reference" => "Patient/$patient_id"],
                        "performer" => [["reference" => "Practitioner/$pract_id"]],
                        "encounter" => [
                            "reference" => "Encounter/$id_encounter",
                            "display" => "Pemeriksaan berat pasien {$row['nm_pasien']}"
                        ],
                        "effectiveDateTime" => $timeObs,
                        "issued" => $timeObs,
                        "valueQuantity" => [
                            "value" => $beratVal,
                            "unit" => "kilogram",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "kg"
                        ]
                    ];

                    // var_dump($payloadberat);
                    // exit();

                    // === KIRIM KE SATU SEHAT ===
                    $resberatFHIR = sendFHIR("Observation", $payloadberat, $token);
                    $statusberat = ($resberatFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                    $id_observation = $resberatFHIR['response']['id'] ?? null;
                    $jsonberat = json_encode($resberatFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // === SIMPAN KE DATABASE ===
                    $stmtberat = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_observationttvberat_new
                        (no_rawat, berat, status, observation_id, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        berat = VALUES(berat),
                        status = VALUES(status),
                        observation_id = VALUES(observation_id),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmtberat->bind_param(
                    "sdsss",
                    $row['no_rawat'],
                    $beratVal,
                    $statusberat,
                    $id_observation,
                    $jsonberat
                );

                $stmtberat->execute();


                    if ($statusberat === 'berhasil') {
                        echo "✅ Observation berat berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                    } else {
                        echo "⚠️ Observation berat gagal dikirim ({$resberatFHIR['http_code']})" . PHP_EOL;
                    }

                } else {
                    echo "ℹ️ Tidak ada data berat untuk {$row['nm_pasien']}." . PHP_EOL;
                }
            } else {
                echo "ℹ️ Data berat tidak ditemukan di database." . PHP_EOL;
            }

            // === LANJUT KE tinggi ===
            lanjut_lingkarperut:


            //lingkarperut
            $sqlLingkarperut = "
                SELECT 
                    o.lingkar_perut, 
                    o.tgl_perawatan, 
                    o.jam_rawat, 
                    pg.no_ktp AS nik_dokter, 
                    pg.nama AS nm_dokter, 
                    lingkar_perut.status
                FROM pemeriksaan_ralan o
                LEFT JOIN reg_periksa rp ON o.no_rawat = rp.no_rawat
                LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
                LEFT JOIN satu_sehat_observationttvlingkarperut_new lingkar_perut ON o.no_rawat = lingkar_perut.no_rawat
                WHERE o.no_rawat = '{$row['no_rawat']}'
                LIMIT 1
            ";

            $reslingkar_perut = $GLOBALS['conn']->query($sqlLingkarperut);

            if ($reslingkar_perut && $reslingkar_perut->num_rows > 0) {
                $lingkar_perut = $reslingkar_perut->fetch_assoc();

                // === CEK APAKAH SUDAH BERHASIL DIKIRIM ===
                if (!empty($lingkar_perut['status']) && $lingkar_perut['status'] === 'berhasil') {
                    echo "ℹ️ Observation lingkar_perut sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_procedure;
                }

                // === CEK APAKAH ADA NILAI lingkar_perut DI PEMERIKSAAN_RALAN ===
                if (!empty($lingkar_perut['lingkar_perut'])) {
                    $lingkar_perutVal = (int)$lingkar_perut['lingkar_perut'];
                    echo "✅ lingkar_perut ditemukan: {$lingkar_perutVal}" . PHP_EOL;

                    $timeObs = date('Y-m-d\TH:i:sP', strtotime($lingkar_perut['tgl_perawatan'].' '.$lingkar_perut['jam_rawat']));
                    $nik_dokter = trim($lingkar_perut['nik_dokter']);
                    $nama_dokter = trim($lingkar_perut['nm_dokter']);

                    // === AMBIL / BUAT PRACTITIONER ===
                    if (!empty($nik_dokter)) {
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                        $pract_id = getResource("Practitioner", $paramPract, $token);

                        if (!$pract_id) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $nik_dokter
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $nama_dokter
                                ]],
                                "active" => true
                            ];
                            $pract_id = createResource("Practitioner", $payloadPract, $token);
                        }
                    } else {
                        echo "⚠️ NIK dokter tidak ditemukan, lewati lingkar_perut." . PHP_EOL;
                        goto lanjut_procedure;
                    }

                    if (!$patient_id) {
                        echo "⚠️ Patient ID tidak ditemukan, lewati lingkar_perut." . PHP_EOL;
                        goto lanjut_procedure;
                    }

                    // === BUAT PAYLOAD OBSERVATION lingkar_perut ===
                    $payloadlingkar_perut = [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [[
                            "coding" => [[
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]]
                        ]],
                        "code" => [
                            "coding" => [[
                                "system" => "http://loinc.org",
                                "code" => "29463-7",
                                "display" => "Body Weight"
                            ]]
                        ],
                        "subject" => ["reference" => "Patient/$patient_id"],
                        "performer" => [["reference" => "Practitioner/$pract_id"]],
                        "encounter" => [
                            "reference" => "Encounter/$id_encounter",
                            "display" => "Pemeriksaan lingkar_perut pasien {$row['nm_pasien']}"
                        ],
                        "effectiveDateTime" => $timeObs,
                        "issued" => $timeObs,
                        "valueQuantity" => [
                            "value" => $lingkar_perutVal,
                            "unit" => "kilogram",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "kg"
                        ]
                    ];

                    // var_dump($payloadlingkar_perut);
                    // exit();

                    // === KIRIM KE SATU SEHAT ===
                    $reslingkar_perutFHIR = sendFHIR("Observation", $payloadlingkar_perut, $token);
                    $statuslingkar_perut = ($reslingkar_perutFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                    $id_observation = $reslingkar_perutFHIR['response']['id'] ?? null;
                    $jsonlingkar_perut = json_encode($reslingkar_perutFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // === SIMPAN KE DATABASE ===
                    $stmtlingkar_perut = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_observationttvlingkarperut_new
                        (no_rawat, lingkar_perut, status, observation_id, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        lingkar_perut = VALUES(lingkar_perut),
                        status = VALUES(status),
                        observation_id = VALUES(observation_id),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmtlingkar_perut->bind_param(
                    "sdsss",
                    $row['no_rawat'],
                    $lingkar_perutVal,
                    $statuslingkar_perut,
                    $id_observation,
                    $jsonlingkar_perut
                );

                $stmtlingkar_perut->execute();


                    if ($statuslingkar_perut === 'berhasil') {
                        echo "✅ Observation lingkar_perut berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                    } else {
                        echo "⚠️ Observation lingkar_perut gagal dikirim ({$reslingkar_perutFHIR['http_code']})" . PHP_EOL;
                    }

                } else {
                    echo "ℹ️ Tidak ada data lingkar_perut untuk {$row['nm_pasien']}." . PHP_EOL;
                }
            } else {
                echo "ℹ️ Data lingkar_perut tidak ditemukan di database." . PHP_EOL;
            }

            // === LANJUT KE tinggi ===
            lanjut_procedure:


            //procedure
            $sqlProcedure = "
            SELECT 
                pp.no_rawat, 
                pp.kode AS kd_procedure,
                ic.deskripsi_panjang AS nm_procedure,
                proc.status,
                d.nm_dokter,
                p.no_ktp as nik_dokter
            FROM prosedur_pasien pp
            INNER JOIN icd9 ic ON ic.kode = pp.kode
            LEFT JOIN satu_sehat_procedure_new proc ON pp.no_rawat = proc.no_rawat
            INNER JOIN reg_periksa rp ON pp.no_rawat = rp.no_rawat
            INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            INNER JOIN pegawai p ON d.kd_dokter = p.nik
            WHERE pp.no_rawat = '{$row['no_rawat']}'
            LIMIT 1
        ";

        $resProcedure = $GLOBALS['conn']->query($sqlProcedure);

        if ($resProcedure && $resProcedure->num_rows > 0) {
            $procedure = $resProcedure->fetch_assoc();

            // === CEK APAKAH SUDAH DIKIRIM ===
            if (!empty($procedure['status']) && $procedure['status'] === 'berhasil') {
                echo "ℹ️ Procedure sudah pernah dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                goto lanjut_clinicalimpression;
            }

            // === VALIDASI DATA ===
            if (empty($procedure['kd_procedure']) || empty($procedure['nm_procedure'])) {
                echo "⚠️ Data procedure tidak lengkap, dilewati." . PHP_EOL;
                goto lanjut_clinicalimpression;
            }

            $kodeProcedure = trim($procedure['kd_procedure']);
            $namaProcedure = trim($procedure['nm_procedure']);
            $tglMulai      = date('Y-m-d\TH:i:sP', strtotime($row['tgl_registrasi']));
            $tglSelesai    = date('Y-m-d\TH:i:sP', strtotime($row['tgl_registrasi'])); // jika tidak ada kolom selesai
            $nik_dokter    = trim($row['nik_dokter']);
            $nama_dokter   = trim($row['nm_dokter']);

            // === CEK PRACTITIONER ===
            if (!empty($nik_dokter)) {
                $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                $pract_id = getResource("Practitioner", $paramPract, $token);

                if (!$pract_id) {
                    echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                    $payloadPract = [
                        "resourceType" => "Practitioner",
                        "identifier" => [[
                            "system" => "https://fhir.kemkes.go.id/id/nik",
                            "value" => $nik_dokter
                        ]],
                        "name" => [[
                            "use" => "official",
                            "text" => $nama_dokter
                        ]],
                        "active" => true
                    ];
                    $pract_id = createResource("Practitioner", $payloadPract, $token);
                }

                // var_dump($pract_id);
                // exit();
            } else {
                echo "⚠️ NIK dokter tidak ditemukan, lewati Procedure." . PHP_EOL;
                goto lanjut_clinicalimpression;
            }

            if (!$patient_id || !$id_encounter) {
                echo "⚠️ Patient atau Encounter ID tidak ditemukan, lewati Procedure." . PHP_EOL;
                goto lanjut_clinicalimpression;
            }

            // === BUAT PAYLOAD Procedure ===
            $payloadProcedure = [
                "resourceType" => "Procedure",
                "status" => "completed",
                "category" => [
                    "coding" => [[
                        "system" => "http://snomed.info/sct",
                        "code" => "103693007",
                        "display" => "Diagnostic procedure"
                    ]],
                    "text" => "Diagnostic procedure"
                ],
                "code" => [
                    "coding" => [[
                        "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                        "code" => $kodeProcedure,
                        "display" => $namaProcedure
                    ]]
                ],
                "subject" => [
                    "reference" => "Patient/$patient_id",
                    "display" => $row['nm_pasien']
                ],
                "encounter" => [
                    "reference" => "Encounter/$id_encounter",
                    "display" => "Prosedur {$row['nm_pasien']} selama kunjungan rawat"
                ],
                "performedPeriod" => [
                    "start" => $tglMulai,
                    "end" => $tglSelesai
                ],
                "performer" => [[
                    "actor" => ["reference" => "Practitioner/$pract_id"]
                ]]
            ];

            // === KIRIM KE SATUSEHAT ===
            $resProcedureFHIR = sendFHIR("Procedure", $payloadProcedure, $token);
            $statusProcedure = ($resProcedureFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
            $id_procedure = $resProcedureFHIR['response']['id'] ?? null;
            $jsonProcedure = json_encode($resProcedureFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // === SIMPAN KE DATABASE ===
            $stmtProcedure = $GLOBALS['conn']->prepare("
            INSERT INTO satu_sehat_procedure_new
                (no_rawat, kode, status, procedure_id, response_message, tanggal_kirim)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                kode = VALUES(kode),
                status = VALUES(status),
                procedure_id = VALUES(procedure_id),
                response_message = VALUES(response_message),
                tanggal_kirim = NOW(),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmtProcedure->bind_param(
            "sssss",
            $procedure['no_rawat'],
            $kodeProcedure,
            $statusProcedure,
            $id_procedure,
            $jsonProcedure
        );

        $stmtProcedure->execute();


            if ($statusProcedure === 'berhasil') {
                echo "✅ Procedure berhasil dikirim untuk {$row['nm_pasien']} ({$namaProcedure})" . PHP_EOL;
            } else {
                echo "⚠️ Procedure gagal dikirim ({$resProcedureFHIR['http_code']})" . PHP_EOL;
            }

        } else {
            echo "ℹ️ Tidak ada data procedure untuk {$row['nm_pasien']}." . PHP_EOL;
        }

        lanjut_clinicalimpression:



        $sqlClinical = "
            SELECT 
                rp.tgl_registrasi,
                p.nm_pasien,
                pr.no_rawat,
                pr.tgl_perawatan,
                pr.jam_rawat,
                rp.kd_dokter,
                d.nm_dokter,
                pg.no_ktp AS nik_dokter,
                pr.penilaian AS clinical,
                dp.kd_penyakit AS kd_diagnosa,
                py.nm_penyakit AS display_diagnosa,
                ssc.id_condition,
                sscl.status
            FROM pemeriksaan_ralan pr
            INNER JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            LEFT JOIN pegawai pg ON rp.kd_dokter = pg.nik
            INNER JOIN diagnosa_pasien dp ON pr.no_rawat = dp.no_rawat
            INNER JOIN penyakit py ON dp.kd_penyakit = py.kd_penyakit
            INNER JOIN satu_sehat_condition_new ssc ON pr.no_rawat = ssc.no_rawat
            LEFT JOIN satu_sehat_clinicalimpression_new sscl ON pr.no_rawat = sscl.no_rawat
            WHERE pr.no_rawat = '{$row['no_rawat']}'
            LIMIT 1
        ";

        $resClinical = $GLOBALS['conn']->query($sqlClinical);

        if ($resClinical && $resClinical->num_rows > 0) {
            $clinical = $resClinical->fetch_assoc();

            // === CEK APAKAH SUDAH DIKIRIM ===
            if (!empty($clinical['status']) && $clinical['status'] === 'berhasil') {
                echo "ℹ️ ClinicalImpression sudah pernah dikirim untuk {$clinical['nm_pasien']} — dilewati." . PHP_EOL;
                goto lanjut_medicationReg;
            }

            // === VALIDASI DATA WAJIB ===
            if (empty($clinical['clinical']) || empty($clinical['kd_diagnosa']) || empty($clinical['id_condition'])) {
                echo "⚠️ Data ClinicalImpression tidak lengkap, dilewati." . PHP_EOL;
                goto lanjut_medicationReg;
            }

            $tgl_perawatan = trim($clinical['tgl_perawatan']);
            $jam_rawat     = trim($clinical['jam_rawat']);
            $tglDateTime   = date('Y-m-d\TH:i:sP', strtotime("$tgl_perawatan $jam_rawat"));

            $nik_dokter    = trim($clinical['nik_dokter']);
            $nama_dokter   = trim($clinical['nm_dokter']);

            // === CEK PRACTITIONER ===
            if (!empty($nik_dokter)) {
                $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                $pract_id = getResource("Practitioner", $paramPract, $token);

                if (!$pract_id) {
                    echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                    $payloadPract = [
                        "resourceType" => "Practitioner",
                        "identifier" => [[
                            "system" => "https://fhir.kemkes.go.id/id/nik",
                            "value" => $nik_dokter
                        ]],
                        "name" => [[
                            "use" => "official",
                            "text" => $nama_dokter
                        ]],
                        "active" => true
                    ];
                    $pract_id = createResource("Practitioner", $payloadPract, $token);
                }
            } else {
                echo "⚠️ NIK dokter tidak ditemukan, lewati ClinicalImpression." . PHP_EOL;
                goto lanjut_medicationReg;
            }

            if (!$patient_id || !$id_encounter) {
                echo "⚠️ Patient atau Encounter ID tidak ditemukan, lewati ClinicalImpression." . PHP_EOL;
                goto lanjut_medicationReg;
            }

            // === BUAT PAYLOAD ClinicalImpression ===
            $payloadClinical = [
                "resourceType" => "ClinicalImpression",
                "status" => "completed",
                "description" => $clinical['clinical'],
                "subject" => [
                    "reference" => "Patient/$patient_id",
                    "display" => $clinical['nm_pasien']
                ],
                "encounter" => [
                    "reference" => "Encounter/$id_encounter",
                    "display" => "Kunjungan {$clinical['nm_pasien']} pada tanggal {$clinical['tgl_perawatan']} dengan nomor {$clinical['no_rawat']}"
                ],
                "effectiveDateTime" => $tglDateTime,
                "date" => $tglDateTime,
                "assessor" => [
                    "reference" => "Practitioner/$pract_id"
                ],
                "summary" => $clinical['clinical'],
                "finding" => [[
                    "itemCodeableConcept" => [
                        "coding" => [[
                            "system" => "http://hl7.org/fhir/sid/icd-10",
                            "code" => $clinical['kd_diagnosa'],
                            "display" => $clinical['display_diagnosa']
                        ]]
                    ],
                    "itemReference" => [
                        "reference" => "Condition/{$clinical['id_condition']}"
                    ]
                ]],
                "prognosisCodeableConcept" => [[
                    "coding" => [[
                        "system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                        "code" => "PR000001",
                        "display" => "Prognosis"
                    ]]
                ]]
            ];

            // var_dump($payloadClinical);
            // exit();

            // === KIRIM KE SATUSEHAT ===
            $resClinicalFHIR = sendFHIR("ClinicalImpression", $payloadClinical, $token);
            $statusClinical = ($resClinicalFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
            $id_clinical = $resClinicalFHIR['response']['id'] ?? null;
            $jsonClinical = json_encode($resClinicalFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // === SIMPAN KE DATABASE ===
            $stmtClinical = $GLOBALS['conn']->prepare("
            INSERT INTO satu_sehat_clinicalimpression_new
                (no_rawat, status, clinicalimpression_id, response_message, tanggal_kirim)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                clinicalimpression_id = VALUES(clinicalimpression_id),
                response_message = VALUES(response_message),
                tanggal_kirim = NOW(),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmtClinical->bind_param(
            "ssss",
            $clinical['no_rawat'],
            $statusClinical,
            $id_clinical,
            $jsonClinical
        );

        $stmtClinical->execute();


            if ($statusClinical === 'berhasil') {
                echo "✅ ClinicalImpression berhasil dikirim untuk {$clinical['nm_pasien']} ({$clinical['display_diagnosa']})" . PHP_EOL;
            } else {
                echo "⚠️ ClinicalImpression gagal dikirim ({$resClinicalFHIR['http_code']})" . PHP_EOL;
            }

        } else {
            echo "ℹ️ Tidak ada data ClinicalImpression untuk {$row['nm_pasien']}." . PHP_EOL;
        }

        lanjut_medicationReg:




        //medicationreq
        // === AMBIL ORGANIZATION ID ===
        $qOrg = $GLOBALS['conn']->query("SELECT organization_id FROM ss_config LIMIT 1");
        $org_id = ($qOrg && $qOrg->num_rows > 0) ? $qOrg->fetch_assoc()['organization_id'] : null;

        if (!$org_id) {
            echo "⚠️ Organization ID tidak ditemukan di tabel ss_config. Proses dihentikan." . PHP_EOL;
            goto lanjut_medicationDispense;
        }

        // === QUERY MEDICATION ===
        $sqlMedication = "
            SELECT 
        reg_periksa.tgl_registrasi,
        reg_periksa.jam_reg,
        reg_periksa.no_rawat,
        reg_periksa.no_rkm_medis,
        pasien.nm_pasien,
        pasien.no_ktp,
        pegawai.nama,
        pegawai.no_ktp AS ktppraktisi,
        satu_sehat_encounter_new.id_encounter,
        satu_sehat_mapping_obat.obat_code,
        satu_sehat_mapping_obat.obat_system,
        resep_dokter.kode_brng,
        satu_sehat_mapping_obat.obat_display,
        satu_sehat_mapping_obat.form_code,
        satu_sehat_mapping_obat.form_system,
        satu_sehat_mapping_obat.form_display,
        satu_sehat_mapping_obat.route_code,
        satu_sehat_mapping_obat.route_system,
        satu_sehat_mapping_obat.route_display,
        satu_sehat_mapping_obat.denominator_code,
        satu_sehat_mapping_obat.denominator_system,
        resep_obat.tgl_peresepan,
        resep_obat.jam_peresepan,
        resep_dokter.jml,
        satu_sehat_medication.id_medication,
        resep_dokter.aturan_pakai,
        resep_dokter.no_resep,
        dokter.kd_dokter,
        dokter.nm_dokter AS nm_dokter,
        pegawai.no_ktp AS nik_dokter,
        IFNULL(satu_sehat_medicationrequest_new.id_medicationrequest,'') AS id_medicationrequest
    FROM reg_periksa
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN resep_obat ON reg_periksa.no_rawat = resep_obat.no_rawat
    INNER JOIN pegawai ON resep_obat.kd_dokter = pegawai.nik
    INNER JOIN dokter ON resep_obat.kd_dokter =  dokter.kd_dokter
    INNER JOIN satu_sehat_encounter_new ON satu_sehat_encounter_new.no_rawat = reg_periksa.no_rawat
    INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
    INNER JOIN satu_sehat_mapping_obat ON satu_sehat_mapping_obat.kode_brng = resep_dokter.kode_brng
    INNER JOIN satu_sehat_medication ON satu_sehat_medication.kode_brng = satu_sehat_mapping_obat.kode_brng
    LEFT JOIN satu_sehat_medicationrequest_new 
    ON satu_sehat_medicationrequest_new.no_resep = resep_dokter.no_resep 
    AND satu_sehat_medicationrequest_new.kode_brng = resep_dokter.kode_brng
    WHERE reg_periksa.no_rawat = '{$row['no_rawat']}'

        ";

        $resMed = $GLOBALS['conn']->query($sqlMedication);

        if ($resMed && $resMed->num_rows > 0) {
            while ($med = $resMed->fetch_assoc()) {

                // === CEK SUDAH PERNAH DIKIRIM ===
                if (!empty($med['id_medicationrequest'])) {
                    echo "ℹ️ MedicationRequest sudah pernah dikirim untuk {$med['nm_pasien']} — dilewati." . PHP_EOL;
                    goto lanjut_medicationDispense;
                }

                // === VALIDASI DATA ===
                if (empty($med['id_medication']) || empty($med['obat_display'])) {
                    echo "⚠️ Data obat tidak lengkap, lewati MedicationRequest." . PHP_EOL;
                    goto lanjut_medicationDispense;
                }

                $idMedication   = trim($med['id_medication']);
                $noResep        = trim($med['no_resep']);
                $kodeBrg        = trim($med['kode_brng']);
                $aturan         = trim($med['aturan_pakai']);
                $namaObat       = trim($med['obat_display']);
                $tglPeresepan   = $med['tgl_peresepan'] . 'T' . $med['jam_peresepan'] . '+07:00';
                $idEncounter    = trim($med['id_encounter']);
                $nik_dokter     = trim($med['nik_dokter']);
                $nama_dokter    = trim($med['nm_dokter']);

                // var_dump($nik_dokter);


                // === CEK PRACTITIONER ===
                if (!empty($nik_dokter)) {
                    $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";

                    $pract_id = getResource("Practitioner", $paramPract, $token);

                    if (!$pract_id) {
                        echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                        $payloadPract = [
                            "resourceType" => "Practitioner",
                            "identifier" => [[
                                "system" => "https://fhir.kemkes.go.id/id/nik",
                                "value" => $nik_dokter
                            ]],
                            "name" => [[
                                "use" => "official",
                                "text" => $nama_dokter
                            ]],
                            "active" => true
                        ];
                        $pract_id = createResource("Practitioner", $payloadPract, $token);
                    }

                    // var_dump($pract_id);
                    // exit();
                } else {
                    echo "⚠️ NIK dokter tidak ditemukan, lewati MedicationRequest." . PHP_EOL;
                    goto lanjut_medicationDispense;
                }

                if (!$patient_id || !$idEncounter) {
                    echo "⚠️ Patient atau Encounter ID tidak ditemukan, lewati MedicationRequest." . PHP_EOL;
                    goto lanjut_medicationDispense;
                }

                // === BUAT PAYLOAD ===
                $signa1 = !empty($med['jml']) ? (float)$med['jml'] : 1; // dosis per kali
                $signa2 = 1; // frequency, bisa disesuaikan

                $route_code   = $med['route_code'] ?? '';
                $route_system = $med['route_system'] ?? '';
                $route_display= $med['route_display'] ?? '';

                $dose_unit   = $med['denominator_code'] ?? 'tablet';
                $dose_system = $med['denominator_system'] ?? 'http://unitsofmeasure.org';
                $dose_code   = $med['denominator_code'] ?? 'tbl';

                $payloadMedReq = [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "system" => "http://sys-ids.kemkes.go.id/prescription/$org_id",
                            "use" => "official",
                            "value" => $noResep
                        ],
                        [
                            "system" => "http://sys-ids.kemkes.go.id/prescription-item/$org_id",
                            "use" => "official",
                            "value" => $kodeBrg
                        ]
                    ],
                    "status" => "completed",
                    "intent" => "order",
                    "category" => [[
                        "coding" => [[
                            "system" => "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                            "code" => "outpatient",
                            "display" => "Outpatient"
                        ]]
                    ]],
                    "medicationReference" => [
                        "reference" => "Medication/$idMedication",
                        "display" => $namaObat
                    ],
                    "subject" => [
                        "reference" => "Patient/$patient_id",
                        "display" => $med['nm_pasien']
                    ],
                    "encounter" => [
                        "reference" => "Encounter/$idEncounter"
                    ],
                    "authoredOn" => $tglPeresepan,
                    "requester" => [
                        "reference" => "Practitioner/$pract_id",
                        "display" => $nama_dokter
                    ],
                    "dosageInstruction" => [[
                        "sequence" => 1,
                        "patientInstruction" => $aturan,
                        "timing" => [
                            "repeat" => [
                                "frequency" => $signa2,
                                "period" => 1,
                                "periodUnit" => "d"
                            ]
                        ],
                        "route" => [
                            "coding" => [[
                                "system" => $route_system,
                                "code" => $route_code,
                                "display" => $route_display
                            ]]
                        ],
                        "doseAndRate" => [[
                            "doseQuantity" => [
                                "value" => $signa1,
                                "unit"  => $dose_unit,
                                "system"=> $dose_system,
                                "code"  => $dose_code
                            ]
                        ]]
                    ]],
                    "dispenseRequest" => [
                        "quantity" => [
                            "value" => (float)$med['jml'],
                            "unit" => $dose_unit,
                            "system" => $dose_system,
                            "code" => $dose_code
                        ],
                        "performer" => [
                            "reference" => "Organization/$org_id"
                        ]
                    ]
                ];


                // var_dump($payloadMedReq);
                // exit();
                // === KIRIM KE SATUSEHAT ===
                $resMedFHIR = sendFHIR("MedicationRequest", $payloadMedReq, $token);
                $statusMed = ($resMedFHIR['http_code'] == 201) ? 'berhasil' : 'gagal';
                $id_medreq = $resMedFHIR['response']['id'] ?? null;
                $jsonMed = json_encode($resMedFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                // === SIMPAN KE DATABASE ===
                $stmtMed = $GLOBALS['conn']->prepare("
                INSERT INTO satu_sehat_medicationrequest_new
                    (no_resep, kode_brng, status, id_medicationrequest, response_message, tanggal_kirim)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    id_medicationrequest = VALUES(id_medicationrequest),
                    response_message = VALUES(response_message),
                    tanggal_kirim = NOW(),
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmtMed->bind_param(
                "sssss",
                $noResep,
                $kodeBrg,
                $statusMed,
                $id_medreq,
                $jsonMed
            );

            $stmtMed->execute();


                if ($statusMed === 'berhasil') {
                    echo "✅ MedicationRequest berhasil dikirim untuk {$med['nm_pasien']} ({$namaObat})" . PHP_EOL;
                } else {
                    echo "⚠️ MedicationRequest gagal dikirim ({$resMedFHIR['http_code']})" . PHP_EOL;
                }
            }
        } else {
            echo "ℹ️ Tidak ada data MedicationRequest untuk {$row['nm_pasien']}." . PHP_EOL;
        }

        lanjut_medicationDispense:


        //medicationdispense
        // === QUERY MEDICATION DISPENSE ===
           $sqlDispense = "
          SELECT 
            reg_periksa.tgl_registrasi,
            reg_periksa.jam_reg,
            reg_periksa.no_rawat,
            reg_periksa.no_rkm_medis,
            pasien.nm_pasien,
            pasien.no_ktp,
            pegawai.nama,
            pegawai.no_ktp AS ktppraktisi,
            satu_sehat_encounter_new.id_encounter,
            satu_sehat_mapping_obat.obat_code,
            satu_sehat_mapping_obat.obat_system,
            detail_pemberian_obat.kode_brng,
            satu_sehat_mapping_obat.obat_display,
            satu_sehat_mapping_obat.form_code,
            satu_sehat_mapping_obat.form_system,
            satu_sehat_mapping_obat.form_display,
            satu_sehat_mapping_obat.route_code,
            satu_sehat_mapping_obat.route_system,
            satu_sehat_mapping_obat.route_display,
            satu_sehat_mapping_obat.denominator_code,
            satu_sehat_mapping_obat.denominator_system,
            resep_obat.tgl_peresepan,
            resep_obat.jam_peresepan,
            detail_pemberian_obat.jml,
            satu_sehat_medication.id_medication,
            aturan_pakai.aturan,
            resep_obat.no_resep,
            IFNULL(satu_sehat_medicationdispense_new.id_medicationdispanse,'') AS id_medicationdispanse,
            detail_pemberian_obat.no_batch,
            detail_pemberian_obat.no_faktur,
            detail_pemberian_obat.tgl_perawatan,
            detail_pemberian_obat.jam,
            satu_sehat_mapping_lokasi_depo_farmasi.id_lokasi_satusehat,
            bangsal.nm_bangsal,
            reg_periksa.status_lanjut,
            satu_sehat_medicationrequest_new.id_medicationrequest
        FROM reg_periksa
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN resep_obat ON reg_periksa.no_rawat = resep_obat.no_rawat
        INNER JOIN pegawai ON resep_obat.kd_dokter = pegawai.nik
        INNER JOIN satu_sehat_encounter_new ON satu_sehat_encounter_new.no_rawat = reg_periksa.no_rawat
        INNER JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = resep_obat.no_rawat
            AND detail_pemberian_obat.tgl_perawatan = resep_obat.tgl_perawatan
            AND detail_pemberian_obat.jam = resep_obat.jam
        INNER JOIN aturan_pakai ON detail_pemberian_obat.no_rawat = aturan_pakai.no_rawat
            AND detail_pemberian_obat.tgl_perawatan = aturan_pakai.tgl_perawatan
            AND detail_pemberian_obat.jam = aturan_pakai.jam
            AND detail_pemberian_obat.kode_brng = aturan_pakai.kode_brng
        INNER JOIN satu_sehat_mapping_obat ON satu_sehat_mapping_obat.kode_brng = detail_pemberian_obat.kode_brng
        INNER JOIN bangsal ON bangsal.kd_bangsal = detail_pemberian_obat.kd_bangsal
        INNER JOIN satu_sehat_mapping_lokasi_depo_farmasi ON satu_sehat_mapping_lokasi_depo_farmasi.kd_bangsal = bangsal.kd_bangsal
        INNER JOIN satu_sehat_medication ON satu_sehat_medication.kode_brng = satu_sehat_mapping_obat.kode_brng
        LEFT JOIN satu_sehat_medicationdispense_new ON satu_sehat_medicationdispense_new.no_rawat = detail_pemberian_obat.no_rawat
        INNER JOIN satu_sehat_medicationrequest_new ON resep_obat.no_resep = satu_sehat_medicationrequest_new.no_resep

            AND satu_sehat_medicationdispense_new.tgl_perawatan = detail_pemberian_obat.tgl_perawatan
            AND satu_sehat_medicationdispense_new.jam = detail_pemberian_obat.jam
            AND satu_sehat_medicationdispense_new.kode_brng = detail_pemberian_obat.kode_brng
            AND satu_sehat_medicationdispense_new.no_batch = detail_pemberian_obat.no_batch
            AND satu_sehat_medicationdispense_new.no_faktur = detail_pemberian_obat.no_faktur
        WHERE reg_periksa.no_rawat = '{$row['no_rawat']}'
        GROUP BY detail_pemberian_obat.kode_brng;



        ";

        $resDispense = $GLOBALS['conn']->query($sqlDispense);

        if ($resDispense && $resDispense->num_rows > 0) {
            while ($disp = $resDispense->fetch_assoc()) {

                // === CEK SUDAH PERNAH DIKIRIM ===
                if (!empty($disp['id_medicationdispanse'])) {
                    echo "ℹ️ MedicationDispense sudah pernah dikirim untuk {$disp['nm_pasien']} ({$disp['obat_display']}) — dilewati." . PHP_EOL;
                    goto lanjut_medicationstatement;
                }

                // === VALIDASI DATA ===
                if (empty($disp['id_medication']) || empty($disp['obat_display'])) {
                    echo "⚠️ Data obat tidak lengkap, lewati MedicationDispense." . PHP_EOL;
                    goto lanjut_medicationstatement;
                }

                // === DATA UTAMA ===
                $idMedication = trim($disp['id_medication']);
                $noResep = trim($disp['no_resep']);
                $kodeBrg = trim($disp['kode_brng']);
                $aturan = trim($disp['aturan']);
                $namaObat = trim($disp['obat_display']);
                $tglPerawatan = $disp['tgl_perawatan'] . 'T' . $disp['jam'] . '+07:00';
                $idEncounter = trim($disp['id_encounter']);
                $nik_dokter = trim($disp['ktppraktisi']);
                $nama_dokter = trim($disp['nama']);
                $noBatch = trim($disp['no_batch']);
                $noFaktur = trim($disp['no_faktur']);
                $jenis_rawat = trim($disp['status_lanjut']);
                $idMedicationRequest = trim($disp['id_medicationrequest']);
            
                // === CEK PRACTITIONER ===
                $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|$nik_dokter";
                $pract_id = getResource("Practitioner", $paramPract, $token);
                if (!$pract_id) {
                    echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                    $payloadPract = [
                        "resourceType" => "Practitioner",
                        "identifier" => [[
                            "system" => "https://fhir.kemkes.go.id/id/nik",
                            "value" => $nik_dokter
                        ]],
                        "name" => [[
                            "use" => "official",
                            "text" => $nama_dokter
                        ]],
                        "active" => true
                    ];
                    $pract_id = createResource("Practitioner", $payloadPract, $token);
                }

                // === BUAT PAYLOAD ===
                 $signa1 = !empty($disp['jml']) ? (float)$med['jml'] : 1; // dosis per kali
                $signa2 = 1; // frequency, bisa disesuaikan


                $payloadDisp = [
                "resourceType" => "MedicationDispense",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/medicationdispense/$org_id",
                        "use" => "official",
                        "value" => $noResep
                    ],
                    [
                        "system" => "http://sys-ids.kemkes.go.id/medicationdispense-item/$org_id",
                        "use" => "official",
                        "value" => $kodeBrg
                    ]
                ],
                "status" => "completed",
                "category" => [
                    "coding" => [
                        [
                            "system"  => "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                            "code"    => ($jenis_rawat == "Ralan" ? "outpatient" : "inpatient"),
                            "display" => ($jenis_rawat == "Ralan" ? "Outpatient" : "Inpatient")
                        ]
                    ]
                ],
                "medicationReference" => [
                    "reference" => "Medication/$idMedication",
                    "display"   => $namaObat
                ],
                "subject" => [
                    "reference" => "Patient/$patient_id",
                    "display"   => $disp['nm_pasien']
                ],
                "context" => [
                    "reference" => "Encounter/$idEncounter"
                ],
                "authorizingPrescription" => [
                    [
                        "reference" => "MedicationRequest/$idMedicationRequest"
                    ]
                ],
                "performer" => [
                    [
                        "actor" => [
                            "reference" => "Practitioner/$pract_id",
                            "display"   => $nama_dokter
                        ]
                    ]
                ],
                "location" => [
                    "reference" => "Location/{$disp['id_lokasi_satusehat']}",
                    "display"   => $disp['nm_bangsal']
                ],
                "quantity" => [
                    "value"  => (float)$disp['jml'],
                    "unit"   => $disp['denominator_code'],
                    "system" => $disp['denominator_system'],
                    "code"   => $disp['denominator_code']
                ],
                "whenPrepared"  => date('c', strtotime("{$disp['tgl_peresepan']} {$disp['jam_peresepan']}")),
                "whenHandedOver" => date('c', strtotime("{$disp['tgl_perawatan']} {$disp['jam']}")),
                "dosageInstruction" => [
                    [
                        "sequence" => 1,
                        "text"     => $aturan,
                        "timing" => [
                            "repeat" => [
                                "frequency" => (int)$signa2,
                                "period"    => 1,
                                "periodUnit"=> "d"
                            ]
                        ],
                        "route" => [
                            "coding" => [
                                [
                                    "system"  => $disp['route_system'],
                                    "code"    => $disp['route_code'],
                                    "display" => $disp['route_display']
                                ]
                            ]
                        ],
                        "doseAndRate" => [
                            [
                                "doseQuantity" => [
                                    "value"  => (float)$signa1,
                                    "unit"   => $disp['denominator_code'],
                                    "system" => $disp['denominator_system'],
                                    "code"   => $disp['denominator_code']
                                ]
                            ]
                        ]
                    ]
                ]
            ];


                // var_dump($payloadDisp);
                // exit();

                // === KIRIM KE SATUSEHAT ===
                $resDispFHIR = sendFHIR("MedicationDispense", $payloadDisp, $token);
                $statusDisp = ($resDispFHIR['http_code'] == 201) ? 'Berhasil' : 'Gagal';
                $id_disp = $resDispFHIR['response']['id'] ?? null;
                $jsonDisp = json_encode($resDispFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                // === SIMPAN KE DATABASE (NEW) ===
               $stmtDisp = $GLOBALS['conn']->prepare("
            INSERT INTO satu_sehat_medicationdispense_new
                (no_rawat, tgl_perawatan, jam, kode_brng, no_batch, no_faktur, status, id_medicationdispanse, response_message, tanggal_kirim)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                id_medicationdispanse = VALUES(id_medicationdispanse),
                response_message = VALUES(response_message),
                tanggal_kirim = NOW()
        ");

        $stmtDisp->bind_param(
            "sssssssss",
            $disp['no_rawat'],
            $disp['tgl_perawatan'],
            $disp['jam'],
            $kodeBrg,
            $noBatch,
            $noFaktur,
            $statusDisp,
            $id_disp,
            $jsonDisp
        );

        $stmtDisp->execute();



                if ($statusDisp === 'Berhasil') {
                    echo "✅ MedicationDispense berhasil dikirim untuk {$disp['nm_pasien']} ({$namaObat})" . PHP_EOL;
                } else {
                    echo "⚠️ MedicationDispense gagal dikirim ({$resDispFHIR['http_code']})" . PHP_EOL;
                }

                lanjut_medicationstatement:
                // label lompat ke sini, bukan continue
            }
        } else {
            echo "ℹ️ Tidak ada data MedicationDispense untuk {$row['nm_pasien']}." . PHP_EOL;
        }


        $sqlStatement = "
        SELECT 
            reg_periksa.tgl_registrasi,
            reg_periksa.jam_reg,
            reg_periksa.no_rawat,
            reg_periksa.no_rkm_medis,
            pasien.nm_pasien,
            pasien.no_ktp,
            pegawai.nama,
            pegawai.no_ktp AS ktppraktisi,
            satu_sehat_encounter_new.id_encounter,
            satu_sehat_mapping_obat.obat_code,
            satu_sehat_mapping_obat.obat_system,
            resep_dokter.kode_brng,
            satu_sehat_mapping_obat.obat_display,
            satu_sehat_mapping_obat.form_code,
            satu_sehat_mapping_obat.form_system,
            satu_sehat_mapping_obat.form_display,
            satu_sehat_mapping_obat.route_code,
            satu_sehat_mapping_obat.route_system,
            satu_sehat_mapping_obat.route_display,
            satu_sehat_mapping_obat.denominator_code,
            satu_sehat_mapping_obat.denominator_system,
            resep_obat.tgl_penyerahan,
            resep_obat.jam_penyerahan,
            resep_dokter.jml,
            satu_sehat_medication.id_medication,
            resep_dokter.aturan_pakai,
            resep_dokter.no_resep,
            IFNULL(satu_sehat_medicationstatement_new.id_medicationstatement,'') AS id_medicationstatement
        FROM reg_periksa
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN resep_obat ON reg_periksa.no_rawat = resep_obat.no_rawat
        INNER JOIN pegawai ON resep_obat.kd_dokter = pegawai.nik
        INNER JOIN satu_sehat_encounter_new ON satu_sehat_encounter_new.no_rawat = reg_periksa.no_rawat
        INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
        INNER JOIN satu_sehat_mapping_obat ON satu_sehat_mapping_obat.kode_brng = resep_dokter.kode_brng
        INNER JOIN satu_sehat_medication ON satu_sehat_medication.kode_brng = satu_sehat_mapping_obat.kode_brng
        INNER JOIN nota_jalan ON nota_jalan.no_rawat = reg_periksa.no_rawat
        LEFT JOIN satu_sehat_medicationstatement_new 
            ON satu_sehat_medicationstatement_new.no_resep = resep_dokter.no_resep 
            AND satu_sehat_medicationstatement_new.kode_brng = resep_dokter.kode_brng
        WHERE resep_obat.tgl_penyerahan <> '0000-00-00'
        AND nota_jalan.tanggal BETWEEN '$today_1' AND '$today_2'
         AND reg_periksa.no_rawat = '{$row['no_rawat']}'

        ";

        $resStatement = $GLOBALS['conn']->query($sqlStatement);

        $qOrg = $GLOBALS['conn']->query("SELECT organization_id FROM ss_config LIMIT 1");
                $org_id = ($qOrg && $qOrg->num_rows > 0) ? $qOrg->fetch_assoc()['organization_id'] : null;

                if (!$org_id) {
                    echo "⚠️ Organization ID tidak ditemukan di tabel ss_config. Proses dihentikan." . PHP_EOL;
                    goto lanjut_medicationDispense;
                }

                // var_dump($org_id);
                // exit();

        if ($resStatement && $resStatement->num_rows > 0) {
            while ($row = $resStatement->fetch_assoc()) {

                // === CEK SUDAH PERNAH DIKIRIM ===
                if (!empty($row['id_medicationstatement'])) {
                    echo "ℹ️ MedicationStatement sudah pernah dikirim untuk {$row['nm_pasien']} ({$row['obat_display']}) — dilewati." . PHP_EOL;
                    goto lanjut_careplan;
                }

                // === VALIDASI DATA ===
                if (empty($row['id_medication']) || empty($row['obat_display'])) {
                    echo "⚠️ Data obat tidak lengkap, lewati MedicationStatement." . PHP_EOL;
                    goto lanjut_careplan;
                }

                // === DATA UTAMA ===
                $idMedication = trim($row['id_medication']);
                $noResep = trim($row['no_resep']);
                $kodeBrg = trim($row['kode_brng']);
                $aturan = trim($row['aturan_pakai']);
                $namaObat = trim($row['obat_display']);
                $tglPenyerahan = $row['tgl_penyerahan'] . 'T' . $row['jam_penyerahan'] . '+07:00';
                $idEncounter = trim($row['id_encounter']);
                $idPasien = trim($row['no_rkm_medis']);

                // === SIGNAS DOSIS ===
                $signa1 = !empty($row['jml']) ? (float)$row['jml'] : 1; // dosis per kali
                $signa2 = 1; // frequency bisa diambil dari aturan pakai jika ada

                // === BUAT PAYLOAD ===
                $payloadStatement = [
                    "resourceType" => "MedicationStatement",
                    "identifier" => [
                        [
                            "system" => "http://sys-ids.kemkes.go.id/medicationstatement/$org_id",
                            "use" => "official",
                            "value" => $noResep . "-" . $kodeBrg
                        ]
                    ],
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/medication-statement-category",
                                "code" => "outpatient", // sesuaikan dengan status_lanjut jika perlu
                                "display" => "Outpatient"
                            ]
                        ]
                    ],
                    "medicationReference" => [
                        "reference" => "Medication/$idMedication",
                        "display" => $namaObat
                    ],
                    "subject" => [
                        "reference" => "Patient/$patient_id",
                        "display" => $row['nm_pasien']
                    ],
                    "dosage" => [
                        [
                            "text" => $aturan,
                            "timing" => [
                                "repeat" => [
                                    "frequency" => $signa2,
                                    "period" => 1,
                                    "periodUnit" => "d"
                                ]
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => $row['route_system'],
                                        "code" => $row['route_code'],
                                        "display" => $row['route_display']
                                    ]
                                ]
                            ],
                            "doseAndRate" => [
                                [
                                    "doseQuantity" => [
                                        "value" => $signa1,
                                        "unit" => $row['denominator_code'],
                                        "system" => $row['denominator_system'],
                                        "code" => $row['denominator_code']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "dateAsserted" => $tglPenyerahan,
                    "informationSource" => [
                        "reference" => "Patient/$patient_id",
                        "display" => $row['nm_pasien']
                    ],
                    "context" => [
                        "reference" => "Encounter/$idEncounter"
                    ],
                    "note" => [
                        ["text" => "Pasien sudah memahami aturan pakai yang dijelaskan oleh petugas & Obat sudah diserahkan ke pasien"]
                    ]
                ];

                // var_dump($payloadStatement);
                // exit();

                // === KIRIM KE SATUSEHAT ===
                $resFHIR = sendFHIR("MedicationStatement", $payloadStatement, $token);
                $status = ($resFHIR['http_code'] == 201) ? 'Berhasil' : 'Gagal';
                $id_statement = $resFHIR['response']['id'] ?? null;
                $jsonResp = json_encode($resFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                // === SIMPAN KE DATABASE ===
                $stmt = $GLOBALS['conn']->prepare("
                    INSERT INTO satu_sehat_medicationstatement_new
                        (no_rawat, kode_brng, no_resep, status, id_medicationstatement, response_message, tanggal_kirim)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        id_medicationstatement = VALUES(id_medicationstatement),
                        response_message = VALUES(response_message),
                        tanggal_kirim = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmt->bind_param(
                    "ssssss",
                    $row['no_rawat'],
                    $kodeBrg,
                    $noResep,
                    $status,
                    $id_statement,
                    $jsonResp
                );

                $stmt->execute();


                if ($status === 'Berhasil') {
                    echo "✅ MedicationStatement berhasil dikirim untuk {$row['nm_pasien']} ({$namaObat})" . PHP_EOL;
                } else {
                    echo "⚠️ MedicationStatement gagal dikirim ({$resFHIR['http_code']})" . PHP_EOL;
                }

                // === LABEL UNTUK LANJUT CAREPLAN / PROSES BERIKUTNYA ===
                lanjut_careplan:
                // di sini akan lompat ke proses selanjutnya jika ada
            }
        } else {
            echo "ℹ️ Tidak ada data MedicationStatement untuk hari ini." . PHP_EOL;
        }




                // Ambil data CarePlan pasien
                $sqlCarePlan = "
                SELECT 
                    reg_periksa.tgl_registrasi,
                    reg_periksa.jam_reg,
                    reg_periksa.no_rawat,
                    reg_periksa.no_rkm_medis,
                    pasien.nm_pasien,
                    pasien.no_ktp,
                    satu_sehat_encounter_new.id_encounter,
                    pemeriksaan_ralan.rtl,
                    pegawai.nama,
                    pegawai.no_ktp AS ktppraktisi,
                    pemeriksaan_ralan.tgl_perawatan,
                    pemeriksaan_ralan.jam_rawat,
                    reg_periksa.status_lanjut,
                    satu_sehat_encounter_new.patient_id,
                    IFNULL(satu_sehat_careplan_new.id_careplan, '') AS id_careplan,
                    IFNULL(satu_sehat_careplan_new.status, '') AS careplan_status
                FROM reg_periksa
                INNER JOIN pasien 
                    ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                INNER JOIN satu_sehat_encounter_new 
                    ON satu_sehat_encounter_new.no_rawat = reg_periksa.no_rawat
                INNER JOIN pemeriksaan_ralan 
                    ON pemeriksaan_ralan.no_rawat = reg_periksa.no_rawat
                INNER JOIN pegawai 
                    ON pemeriksaan_ralan.nip = pegawai.nik
                LEFT JOIN satu_sehat_careplan_new 
                    ON satu_sehat_careplan_new.no_rawat = pemeriksaan_ralan.no_rawat
                    AND satu_sehat_careplan_new.tgl_perawatan = pemeriksaan_ralan.tgl_perawatan
                    AND satu_sehat_careplan_new.jam_rawat = pemeriksaan_ralan.jam_rawat
                WHERE pemeriksaan_ralan.rtl <> ''
                    AND reg_periksa.no_rawat = '{$row['no_rawat']}'
                ";

                $resCarePlan = $GLOBALS['conn']->query($sqlCarePlan);
                // var_dump($sqlCarePlan);
                // Ambil organization ID
                $qOrg = $GLOBALS['conn']->query("SELECT organization_id FROM ss_config LIMIT 1");
                $org_id = ($qOrg && $qOrg->num_rows > 0) ? $qOrg->fetch_assoc()['organization_id'] : null;

                if (!$org_id) {
                    echo "⚠️ Organization ID tidak ditemukan di tabel ss_config. Proses dihentikan." . PHP_EOL;
                    return; // hentikan eksekusi
                }

                if ($resCarePlan && $resCarePlan->num_rows > 0) {
                    while ($row = $resCarePlan->fetch_assoc()) {

                        // --- Cek status CarePlan ---
                        $careplanStatus = strtolower(trim($row['careplan_status'] ?? ''));
                        // var_dump($careplanStatus);

                        if ($careplanStatus === 'berhasil') {
                            echo "ℹ️ CarePlan sudah berhasil dikirim untuk {$row['nm_pasien']} — dilewati." . PHP_EOL;
                            continue; // lompat ke pasien berikutnya
                        }

                        // --- Cek Practitioner ---
                        $paramPract = "identifier=https://fhir.kemkes.go.id/id/nik|" . $row['ktppraktisi'];
                        $idpraktisi = getResource("Practitioner", $paramPract, $token);

                        if (!$idpraktisi) {
                            echo "ℹ️ Practitioner belum ada, membuat baru..." . PHP_EOL;
                            $payloadPract = [
                                "resourceType" => "Practitioner",
                                "identifier" => [[
                                    "system" => "https://fhir.kemkes.go.id/id/nik",
                                    "value" => $row['ktppraktisi']
                                ]],
                                "name" => [[
                                    "use" => "official",
                                    "text" => $row['nama']
                                ]],
                                "active" => true
                            ];
                            $idpraktisi = createResource("Practitioner", $payloadPract, $token);
                        }

                        // --- Buat payload CarePlan ---
                        $category = ($row['status_lanjut'] == "Ralan") ? [
                            "coding" => [[
                                "system" => "http://snomed.info/sct",
                                "code" => "736271009",
                                "display" => "Outpatient care plan"
                            ]]
                        ] : [
                            "coding" => [[
                                "system" => "http://snomed.info/sct",
                                "code" => "736353004",
                                "display" => "Inpatient care plan"
                            ]]
                        ];

                        $payloadCarePlan = [
                            "resourceType" => "CarePlan",
                            "identifier" => [
                                "system" => "http://sys-ids.kemkes.go.id/careplan/$org_id",
                                "value"  => $row['no_rawat']
                            ],
                            "title" => "Instruksi Medik dan Keperawatan Pasien",
                            "status" => "active",
                            "category" => [$category],
                            "intent" => "plan",
                            "description" => str_replace(["\r\n","\r","\n","\n\r","\t"], ["<br>","<br>","<br>","<br>"," "], $row['rtl']),
                            "subject" => [
                                "reference" => "Patient/".$row['patient_id'],
                                "display" => $row['nm_pasien']
                            ],
                            "encounter" => [
                                "reference" => "Encounter/".$row['id_encounter'],
                                "display" => "Kunjungan {$row['nm_pasien']} pada tanggal {$row['tgl_registrasi']} dengan nomor kunjungan {$row['no_rawat']}"
                            ],
                            "created" => $row['tgl_perawatan']."T".$row['jam_rawat']."+07:00",
                            "author" => [
                                "reference" => "Practitioner/$idpraktisi",
                                "display" => $row['nama']
                            ]
                        ];

                        // var_dump($payloadCarePlan); exit();

                        // --- Kirim ke SATUSEHAT ---
                        $resFHIR = sendFHIR("CarePlan", $payloadCarePlan, $token);
                        $status = ($resFHIR['http_code'] == 201) ? 'Berhasil' : 'Gagal';
                        $id_careplan = $resFHIR['response']['id'] ?? null;
                        $jsonResp = json_encode($resFHIR['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                        // --- Simpan ke database ---
                        $stmt = $GLOBALS['conn']->prepare("
                            INSERT INTO satu_sehat_careplan_new
                                (no_rawat, tgl_perawatan, jam_rawat, status, id_careplan, response_message, tanggal_kirim)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE
                                status = VALUES(status),
                                id_careplan = VALUES(id_careplan),
                                response_message = VALUES(response_message),
                                tanggal_kirim = NOW(),
                                updated_at = CURRENT_TIMESTAMP
                        ");

                        if (!$stmt) {
                            die("Prepare failed: (" . $GLOBALS['conn']->errno . ") " . $GLOBALS['conn']->error);
                        }

                        $stmt->bind_param(
                            "ssssss",
                            $row['no_rawat'],
                            $row['tgl_perawatan'],
                            $row['jam_rawat'],
                            $status,
                            $id_careplan,
                            $jsonResp
                        );
                        $stmt->execute();

                        if ($status === 'Berhasil') {
                            echo "✅ CarePlan berhasil dikirim untuk {$row['nm_pasien']}" . PHP_EOL;
                        } else {
                            echo "⚠️ CarePlan gagal dikirim ({$resFHIR['http_code']})" . PHP_EOL;
                        }
                    }
                } else {
                    echo "ℹ️ Tidak ada data CarePlan untuk {$row['no_rawat']} " . PHP_EOL;
                }


        }


    echo PHP_EOL . "=== Iterasi selesai, tunggu 30 detik... ===" . PHP_EOL;
    sleep(18000);
}
