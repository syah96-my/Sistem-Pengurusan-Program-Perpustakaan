<?php
if (!function_exists('gm_env')) {
    function gm_env($key, $default = '')
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

return [
    'domain'  => gm_env('GM_APP_URL', 'http://localhost/program-register'),
    'host'    => gm_env('GM_DB_HOST', 'localhost'),
    'db'      => gm_env('GM_DB_NAME', 'program_register'),
    'user'    => gm_env('GM_DB_USER', 'root'),
    'pass'    => gm_env('GM_DB_PASS', ''),
    'charset' => gm_env('GM_DB_CHARSET', 'utf8mb4'),
];
?>
