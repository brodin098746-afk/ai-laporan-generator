<?php
// api/helper.php

function call_openai($prompt, $config) {
    $payload = [
        'model'       => $config['model'],
        'messages'    => [
            ['role' => 'system', 'content' => 'Anda adalah asisten penulis laporan resmi pemerintah Indonesia.'],
            ['role' => 'user',   'content' => $prompt],
        ],
        'max_tokens'  => $config['max_tokens'],
        'temperature' => $config['temperature'],
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['openai_api_key'],
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 90,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if($err) return ['error' => "cURL error: $err"];
    $json = json_decode($response, true);
    if(isset($json['error'])) return ['error' => $json['error']['message']];
    return ['success' => true, 'text' => $json['choices'][0]['message']['content'] ?? ''];
}

function markdown_to_html($text) {
    $html = nl2br(htmlspecialchars($text));
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.*)$/m',  '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.*)$/m',   '<h1>$1</h1>', $html);
    $html = preg_replace('/^\s*-\s+(.*)$/m', '<li>$1</li>', $html);
    $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
    $html = preg_replace('/<\/ul>\s*<ul>/s', '', $html);
    return $html;
}

function build_html($config, $data, $isi_ai) {
    $isi_html = markdown_to_html($isi_ai);
    $petugas = $data['petugas'];
    $tgl = date('d F Y', strtotime($data['tgllaporan']));
    $jenis = htmlspecialchars($data['jenis_laporan']);

    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
@page { margin: 2cm 2cm 2cm 2.5cm; }
body { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.6; }
.kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 25px; }
.kop h1 { font-size: 14pt; margin: 0; font-weight: bold; }
.kop h2 { font-size: 13pt; margin: 3px 0; font-weight: bold; }
.kop p  { font-size: 9pt; margin: 2px 0; }
.logo { width: 80px; float: left; }
.judul { text-align: center; font-weight: bold; font-size: 14pt; margin: 20px 0; text-decoration: underline; }
h2 { font-size: 13pt; margin: 20px 0 10px 0; }
h3 { font-size: 12pt; margin: 15px 0 8px 0; }
ul { margin: 8px 0 8px 25px; }
.ttd { margin-top: 40px; width: 100%; }
.ttd td { text-align: center; vertical-align: top; }
.ttd .nama { margin-top: 70px; font-weight: bold; text-decoration: underline; }
</style></head><body>

<div class="kop">
    <img src="{$config['logo_url']}" class="logo">
    <h1>{$config['kementerian']}</h1>
    <h2>{$config['direktorat']}</h2>
    <p>{$config['alamat_kop']}</p>
    <p>{$config['telp_kop']} | {$config['website_kop']}</p>
</div>

<div class="judul">LAPORAN KEGIATAN</div>
<div style="text-align:center;font-weight:bold;margin-bottom:20px;">$jenis</div>

$isi_html

<table class="ttd"><tr>
    <td style="width:50%"></td>
    <td style="width:50%">
        Dibuat di: {$petugas['wilayah']}<br>
        Pada tanggal: {$tgl}<br>
        {$petugas['jabatan']}
        <div class="nama">{$petugas['nama']}</div>
        NIP. {$petugas['nip']}
    </td>
</tr></table>

</body></html>
HTML;
}
