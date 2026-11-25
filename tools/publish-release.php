<?php

// 1. Get token from git credential
$process = proc_open(
    'git credential fill',
    [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes
);

fwrite($pipes[0], "protocol=https\nhost=github.com\n\n");
fclose($pipes[0]);

$credOutput = stream_get_contents($pipes[1]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

preg_match('/password=(.+)/', $credOutput, $matches);
$token = trim($matches[1] ?? '');
if (empty($token)) {
    echo "ERROR: Token not found in git credentials\n";
    exit(1);
}

echo "Token loaded successfully.\n";

$headers = [
    'User-Agent: DomainSystemReleaseScript',
    'Authorization: Bearer ' . $token,
    'Accept: application/vnd.github+json'
];

// Check if release v2.8.9 exists
$ch = curl_init('https://api.github.com/repos/hadelrachid/domain-system-plugins/releases/tags/v2.8.9');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$release = json_decode($resp, true);

if ($code === 404 || empty($release['id'])) {
    echo "Creating release v2.8.9 on GitHub...\n";
    $payload = json_encode([
        'tag_name' => 'v2.8.9',
        'name' => 'Release v2.8.9',
        'body' => 'Official stable release v2.8.9 of domain-system-plugins',
        'draft' => false,
        'prerelease' => false
    ]);

    $ch = curl_init('https://api.github.com/repos/hadelrachid/domain-system-plugins/releases');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array_merge($headers, ['Content-Type: application/json'])
    ]);
    $resp = curl_exec($ch);
    $release = json_decode($resp, true);
    curl_close($ch);

    if (empty($release['id'])) {
        echo "Failed to create release: " . $resp . "\n";
        exit(1);
    }
    echo "Release v2.8.9 created! ID: " . $release['id'] . "\n";
} else {
    echo "Release v2.8.9 already exists (ID: " . $release['id'] . ").\n";
}

$uploadUrlTemplate = preg_replace('/\{\?name,label\}/', '', $release['upload_url']);

// List files to upload
$distDir = 'D:/xampp/htdocs/domain-system-plugins/dist';
$files = glob($distDir . '/*');
$files[] = 'D:/xampp/htdocs/domain-system-plugins/marketplace.json';

// Fetch existing assets to avoid 422 duplicate error
$existingAssets = [];
foreach ($release['assets'] ?? [] as $asset) {
    $existingAssets[$asset['name']] = $asset['id'];
}

foreach ($files as $file) {
    if (!is_file($file)) continue;
    $filename = basename($file);
    
    // If asset exists, delete it first to replace with latest
    if (isset($existingAssets[$filename])) {
        echo "Deleting old asset {$filename} (ID: {$existingAssets[$filename]})...\n";
        $delCh = curl_init("https://api.github.com/repos/hadelrachid/domain-system-plugins/releases/assets/{$existingAssets[$filename]}");
        curl_setopt_array($delCh, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);
        curl_exec($delCh);
        curl_close($delCh);
    }

    echo "Uploading {$filename} (" . filesize($file) . " bytes)...\n";
    $uploadUrl = $uploadUrlTemplate . '?name=' . urlencode($filename);
    $fileData = file_get_contents($file);

    $upCh = curl_init($uploadUrl);
    curl_setopt_array($upCh, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fileData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'User-Agent: DomainSystemReleaseScript',
            'Authorization: Bearer ' . $token,
            'Accept: application/vnd.github+json',
            'Content-Type: application/octet-stream'
        ]
    ]);
    $upResp = curl_exec($upCh);
    $upCode = curl_getinfo($upCh, CURLINFO_HTTP_CODE);
    curl_close($upCh);

    if ($upCode >= 200 && $upCode < 300) {
        echo "Uploaded {$filename} successfully.\n";
    } else {
        echo "Failed uploading {$filename} (HTTP {$upCode}): {$upResp}\n";
    }
}

echo "All assets processed.\n";
