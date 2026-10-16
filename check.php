<?php

/*
 * Namingo Registrar
 *
 * Copyright (c) 2023-2026 Taras Kondratyuk
 * Copyright (c) 2025-2026 Namingo contributors
 * Copyright (c) 2026 Terbora Ltd.
 *
 * Licensed under the Apache License, Version 2.0.
 * May contain portions of FOSSBilling sample modules, also licensed under Apache-2.0.
 * See the LICENSE file for the full license text.
 *
 * SPDX-License-Identifier: Apache-2.0
 */

// ==========================================
// Start editing below: Add your WHOIS and RDAP server hostnames here
// ==========================================

// WHOIS server
$whoisServer = 'whois.example.com';

// RDAP server
$rdap_url = 'rdap.example.com';

// ==========================================
// Stop editing above: No further modifications are necessary
// ==========================================

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$domain = $_POST['domain'] ?? null;
$type = $_POST['type'] ?? null;

if (!is_string($domain) || !is_string($type) ||
    $domain === '' || strlen($domain) > 253 ||
    !in_array($type, ['whois', 'rdap'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input.']);
    exit;
}

$rdapServer = 'https://' . $rdap_url . '/domain/';

$sanitized_domain = filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

// Check if the domain is in Unicode and convert it to Punycode
if (mb_check_encoding($domain, 'UTF-8') && !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
    $punycodeDomain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

    if ($punycodeDomain !== false) {
        $domain = $punycodeDomain;
    } else {
        echo json_encode(['error' => 'Invalid domain.']);
        exit;
    }
}

$sanitized_domain = filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

if ($sanitized_domain) {
    $domain = $sanitized_domain;
} else {
    echo json_encode(['error' => 'Invalid domain.']);
    exit;
}

if ($type === 'whois') {
    $output = '';
    $socket = @fsockopen($whoisServer, 43, $errno, $errstr, 5);

    if (!$socket) {
        echo json_encode(['error' => "Error fetching WHOIS data."]);
        exit;
    }
        
    stream_set_timeout($socket, 2);
    $deadline = microtime(true) + 10;

    if (fwrite($socket, $domain . "\r\n") === false) {
        fclose($socket);
        http_response_code(502);
        echo json_encode(['error' => 'WHOIS query failed.']);
        exit;
    }

    while (!feof($socket) && microtime(true) < $deadline) {
        $chunk = fread($socket, min(8192, 1048577 - strlen($output)));
        if ($chunk === false || stream_get_meta_data($socket)['timed_out']) {
            break;
        }
        $output .= $chunk;
        if (strlen($output) > 1048576) {
            break;
        }
    }
    $complete = feof($socket);

    fclose($socket);

    if (!$complete || strlen($output) > 1048576) {
        http_response_code(502);
        echo json_encode(['error' => 'WHOIS timeout or response too large.']);
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
} elseif ($type === 'rdap') {
    $ch = curl_init();
    $output = '';
    $tooLarge = false;
    curl_setopt($ch, CURLOPT_URL, $rdapServer . rawurlencode($domain));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, $data) use (&$output, &$tooLarge) {
        if (strlen($output) + strlen($data) > 1048576) {
            $tooLarge = true;
            return 0;
        }
        $output .= $data;
        return strlen($data);
    });

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($result === false || ($status !== 404 && ($status < 200 || $status >= 300))) {
        http_response_code(502);
        echo json_encode(['error' => $tooLarge ? 'RDAP response too large.' : 'Error fetching RDAP data.']);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    if (!$output) {
        echo json_encode(['error' => 'Error fetching RDAP data.']);
        exit;
    }

    header('Content-Type: application/rdap+json; charset=utf-8');
}

echo $output;