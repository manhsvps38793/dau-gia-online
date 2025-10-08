<?php

return [
    /*
     * Paths áp dụng CORS (api/* cho tất cả API routes, sanctum/csrf-cookie cho CSRF).
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
     * Allowed methods (GET, POST, etc.).
     */
    'allowed_methods' => ['*'],  // Cho phép tất cả

    /*
     * Allowed origins – KHÔNG dùng '*' nếu có credentials (như JS của bạn).
     */
    'allowed_origins' => [
        'http://127.0.0.1:5500',  // Origin frontend (Live Server – kiểm tra F12 > Network)
        'http://localhost:5500',   // Fallback
         // Frontend chạy trên React/Vite hoặc Node dev server
    'http://127.0.0.1:3001',
    'http://localhost:3001',
    'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],


    /*
     * Allowed origins patterns (regex nếu cần).
     */
    'allowed_origins_patterns' => [],

    /*
     * Allowed headers.
     */
    'allowed_headers' => ['*'],

    /*
     * Exposed headers (cho client đọc).
     */
    'exposed_headers' => [],

    /*
     * Max age cho preflight cache (giây).
     */
    'max_age' => 0,

    /*
     * Supports credentials (cookie/CSRF) – BẮT BUỘC true cho JS credentials: 'include'.
     */
    'supports_credentials' => true,
];
