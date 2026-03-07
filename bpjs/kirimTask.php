<?php
date_default_timezone_set('UTC'); // wajib pakai UTC

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allData = $_POST['data'] ?? [];
    $errorMessages = [];
    $successCount = 0;

    foreach ($allData as $rowIndex => $row) {
        $kodebooking = $row['kodebooking'];
        $tasks = $row['task'] ?? [];

        foreach ([3, 4, 5, 6, 7] as $taskno) {
            if (!empty($tasks[$taskno])) {
                $payload = [
                    'kodebooking' => $kodebooking,
                    'taskid'      => $taskno,
                    'waktu'       => (int)$tasks[$taskno],
                ];

                $response = kirimTaskKeBPJS($payload);
                $code = $response['metadata']['code'] ?? 500;
                $message = $response['metadata']['message'] ?? 'Tidak ada respon dari server BPJS.';

                if (!in_array($code, [200, 208])) {
                    $errorMessages[] = "[$kodebooking] Task $taskno gagal: $message";
                } else {
                    $successCount++;
                }
            }
        }
    }

    if (empty($errorMessages)) {
        echo "<script>alert('Semua task berhasil dikirim! Total sukses: {$successCount}'); window.location.href='?page=task_1';</script>";
    } else {
        $errors = implode("\\n", $errorMessages);
        echo "<script>alert('Beberapa task gagal terkirim:\\n{$errors}'); window.location.href='?page=task_1';</script>";
    }
}

function generateBpjsHeaders() {
    $cons_id = '22020';
    $secret_key = '3aLBB8C8D8';
    $user_key = '1cae203f209aa3d28db949c8a3806069';
    $timestamp = time();
    $signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));

    return [
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "user_key: $user_key",
        "Content-Type: application/json",
    ];
}

function kirimTaskKeBPJS($payload) {
    $dataJson = json_encode($payload);
    $url = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/updatewaktu";
    $headers = generateBpjsHeaders();

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'metadata' => [
                'code' => 500,
                'message' => "CURL Error: $error"
            ]
        ];
    }

    curl_close($ch);
    return json_decode($response, true);
}
