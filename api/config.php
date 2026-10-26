<?php
// api/config.php
return [
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: 'sk-xxx',
    'model'          => 'gpt-4o-mini',
    'max_tokens'     => 2000,
    'temperature'    => 0.7,
    'secret_key'     => getenv('API_SECRET') ?: 'ganti-dengan-random-string-panjang',
    
    'kementerian'    => 'KEMENTERIAN SOSIAL REPUBLIK INDONESIA',
    'direktorat'     => 'DIREKTORAT PERLINDUNGAN SOSIAL NON KEBENCANAAN',
    'alamat_kop'     => 'Jalan Salemba Raya No. 28, Jakarta Pusat 10430',
    'telp_kop'       => 'Telp. (021) 3103591, Fax. (021) 3103592',
    'website_kop'    => 'www.kemsos.go.id',
];
