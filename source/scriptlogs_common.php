<?php

function scriptlogs_default_cfg()
{
    return [
        'REFRESH_ENABLED' => '1',
        'REFRESH_INTERVAL' => '10',
        'ENABLED_SCRIPTS' => '',
        'SHOW_IDLE_LOGS' => '0',
        'LOG_FONT_SIZE' => '1rem',
        'REMOVE_EMPTY_LOG_LINES' => '1'
    ];
}

function scriptlogs_read_cfg($plugin_name = 'scriptlogs')
{
    $cfg = parse_plugin_cfg($plugin_name);
    if (!is_array($cfg)) {
        $cfg = [];
    }

    return array_merge(scriptlogs_default_cfg(), $cfg);
}

function scriptlogs_allowed_font_sizes()
{
    return ['0.75rem', '0.875rem', '1rem', '1.125rem', '1.25rem'];
}

function scriptlogs_tail_limits()
{
    return ['lines' => 100, 'bytes' => 262144];
}

function scriptlogs_json_options()
{
    $options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $options |= JSON_INVALID_UTF8_SUBSTITUTE;
    }

    return $options;
}

function scriptlogs_t($text)
{
    return function_exists('_') ? _($text) : $text;
}

function scriptlogs_is_foreground_running($ps_output, $script_name)
{
    if (!is_string($ps_output) || $ps_output === '') {
        return false;
    }

    $script_path = '/tmp/user.scripts/tmpScripts/' . $script_name . '/script';
    $pattern = '/(?:^|\s)(?:\S+\/)?startScript\.sh\s+' . preg_quote($script_path, '/') . '(?:\s|$)/';
    $lines = preg_split('/\r\n|\r|\n/', $ps_output);
    if (!is_array($lines)) {
        return false;
    }

    foreach ($lines as $line) {
        if (preg_match($pattern, $line) === 1) {
            return true;
        }
    }

    return false;
}

function scriptlogs_normalize_script_list($items)
{
    if (!is_array($items)) {
        return [];
    }

    $normalized = [];
    $seen = [];
    foreach ($items as $item) {
        $name = trim((string)$item);
        if ($name === '' || isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;
        $normalized[] = $name;
    }

    return $normalized;
}

function scriptlogs_parse_enabled_scripts($raw_value)
{
    $raw_value = is_string($raw_value) ? trim($raw_value) : '';
    if ($raw_value === '') {
        return [];
    }

    if (strpos($raw_value, 'json:') === 0) {
        $encoded = substr($raw_value, 5);
        if ($encoded !== '') {
            $decoded_json = base64_decode($encoded, true);
            if (is_string($decoded_json) && $decoded_json !== '') {
                $decoded = json_decode($decoded_json, true);
                if (is_array($decoded)) {
                    return scriptlogs_normalize_script_list($decoded);
                }
            }
        }
    }

    return scriptlogs_normalize_script_list(explode(',', $raw_value));
}

function scriptlogs_encode_enabled_scripts($scripts)
{
    $normalized = scriptlogs_normalize_script_list($scripts);
    $json = json_encode($normalized);
    if (!is_string($json)) {
        $json = '[]';
    }

    return 'json:' . base64_encode($json);
}
