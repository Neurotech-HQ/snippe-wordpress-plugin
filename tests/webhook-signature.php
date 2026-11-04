<?php

/**
 * php tests/webhook-signature.php
 */

define('ABSPATH', __DIR__);

require dirname(__DIR__) . '/includes/class-snippe-api.php';

$secret = 'whsec_test';
$body = '{"type":"payment.completed","data":{"reference":"ref-1"}}';
$timestamp = (string) time();
$good = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

function check($name, $actual, $expected) {
    if ($actual !== $expected) {
        fwrite(STDERR, "failed: {$name}\n");
        exit(1);
    }
}

check('valid', Snippe_API::verify_webhook_signature($body, $good, $secret, $timestamp), true);
check('sha256 prefix', Snippe_API::verify_webhook_signature($body, 'sha256=' . $good, $secret, $timestamp), true);
check('upper hex', Snippe_API::verify_webhook_signature($body, strtoupper($good), $secret, $timestamp), true);
check('missing signature', Snippe_API::verify_webhook_signature($body, '', $secret, $timestamp), false);
check('missing secret', Snippe_API::verify_webhook_signature($body, $good, '', $timestamp), false);
check('missing timestamp', Snippe_API::verify_webhook_signature($body, $good, $secret, ''), false);
check('body only', Snippe_API::verify_webhook_signature($body, hash_hmac('sha256', $body, $secret), $secret, $timestamp), false);
check('tampered body', Snippe_API::verify_webhook_signature($body . ' ', $good, $secret, $timestamp), false);

$old = (string) (time() - 301);
$old_sig = hash_hmac('sha256', $old . '.' . $body, $secret);
check('stale', Snippe_API::verify_webhook_signature($body, $old_sig, $secret, $old), false);

echo "ok\n";
