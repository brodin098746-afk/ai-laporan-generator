<?php
// api/generate.php — Endpoint utama
header('Content-Type: application/json');
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helper.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$config = require __DIR__ . '/config.php';

// --- 1. Autentikasi via secret key ---
$secret = $_SERVER['HTTP_X_API_SECRET'] ?? '';
if($secret !== $config['secret_key']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- 2. Terima JSON payload ---
$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['jenis_laporan'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

// --- 3. Susun prompt AI ---
$petugas = $input['petugas'] ?? [];
$kegiatan = $input['kegiatan'] ?? [];
$kpm = $input['kpm'] ?? [];
$jenis = $input['jenis_laporan'];

$prompt = <<<PROMPT
Anda adalah ASN PPPK di Direktorat Perlindungan Sosial Non Kebencanaan, Kementerian Sosial RI.
Tugas: tulis LAPORAN KEGIATAN RESMI dengan bahasa Indonesia formal, deskriptif, profesional.

DATA:
- Jenis Laporan/RHK: {$jenis}
- Nama Petugas: {$petugas['nama']}
- NIP: {$petugas['nip']}
- Jabatan: {$petugas['jabatan']}
- Unit Kerja: {$petugas['unit']}
- Wilayah Tugas: {$petugas['wilayah']}

KEGIATAN:
- Hari/Tanggal: {$kegiatan['hari']}, {$kegiatan['tanggal']}
- Waktu: {$kegiatan['waktu']}
- Lokasi: {$kegiatan['lokasi']}
- Agenda: {$kegiatan['agenda']}
- Pemateri/Pimpinan: {$kegiatan['pemateri']}
- Peserta: {$kegiatan['peserta']}
- Kelompok: {$kegiatan['kelompok']}
- Modul: {$kegiatan['modul']}
- Sesi: {$kegiatan['sesi']}

KPM:
- Nama: {$kpm['nama']}
- NIK: {$kpm['nik']}
- No KK: {$kpm['nokk']}
- Alamat: {$kpm['alamat']}, Desa {$kpm['desa']}, Kec. {$kpm['kecamatan']}, Kab. {$kpm['kabupaten']}

STRUKTUR LAPORAN (WAJIB):
1. PENDAHULUAN (Gambaran Umum, Maksud & Tujuan, Ruang Lingkup, Dasar Pelaksanaan)
2. PELAKSANA DAN KEGIATAN YANG DILAKSANAKAN
3. HASIL YANG DICAPAI (4-5 poin)
4. KESIMPULAN DAN SARAN
5. PENUTUP

ATURAN:
- Bahasa Indonesia baku, EYD.
- Sesuaikan narasi dengan jenis RHK: "{$jenis}".
- Jika data KPM kosong, hilangkan bagian KPM.
- Output hanya narasi laporan, tanpa penjelasan tambahan.
PROMPT;

// --- 4. Panggil AI ---
$ai = call_openai($prompt, $config);
if(isset($ai['error'])) {
    http_response_code(500);
    echo json_encode(['error' => 'AI error: ' . $ai['error']]);
    exit;
}
$isi_ai = $ai['text'];

// --- 5. Build HTML ---
$config['logo_url'] = (isset($_SERVER['HTTPS']) ? 'https' : 'http') 
                    . '://' . $_SERVER['HTTP_HOST'] . '/assets/logo_kemsos.png';
$html = build_html($config, $input + ['petugas' => $petugas], $isi_ai);

// --- 6. Render PDF ---
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// --- 7. Simpan PDF & return URL ---
$dir = __DIR__ . '/../laporan/';
if(!is_dir($dir)) mkdir($dir, 0777, true);
$filename = 'laporan_' . $input['id'] . '_' . time() . '.pdf';
file_put_contents($dir . $filename, $dompdf->output());

$pdf_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') 
         . '://' . $_SERVER['HTTP_HOST'] . '/laporan/' . $filename;

echo json_encode([
    'success'  => true,
    'pdf_url'  => $pdf_url,
    'isi_ai'   => $isi_ai,
]);
