<?php

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
