<?php

$toolCall = [
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'mcp/tool/call',
    'params' => [
        'name' => 'clock',
        'arguments' => []
    ],
];

// VERY IMPORTANT: Output must be a single clean JSON line followed by a newline
echo json_encode($toolCall, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
