<?php
$keys = [
    'SB-Mid-server-bNWLbYJDpX4caQQ83Vme7Yva', // small L
    'SB-Mid-server-bNWIbYJDpX4caQQ83Vme7Yva', // cap I
    'SB-Mid-server-bNWLbYJDpx4caQQ83Vme7Yva', 
];

foreach ($keys as $key) {
    $ch = curl_init('https://app.sandbox.midtrans.com/snap/v1/transactions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($key . ':')
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'transaction_details' => ['order_id' => 'test-' . time(), 'gross_amount' => 10000]
    ]));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Key: $key -> HTTP Code: $http_code\n";
    if ($http_code != 401) {
        echo "Response: $response\n";
    }
}
