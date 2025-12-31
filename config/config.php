<?php
/**
 * Configuration file for Project Linkon
 * 
 * This file contains all the configuration settings for the application.
 * Copy this file to config.php and update the values accordingly.
 */

return [
    // Database configuration
    'database' => [
        'driver' => 'mysql',  // mysql, pgsql, sqlite
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'linkon',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],

    // Encryption configuration
    'encryption' => [
        'method' => 'aes-256-gcm',  // AES-256-GCM for authenticated encryption
        'key' => getenv('ENCRYPTION_KEY') ?: 'CHANGE_THIS_TO_A_SECURE_32_BYTE_KEY!',
    ],

    // Application configuration
    'app' => [
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost',
        'link_length' => 8,  // Length of generated short links
        'debug' => getenv('APP_DEBUG') ?: false,
    ],

    // Password hashing configuration
    'password' => [
        'algo' => PASSWORD_ARGON2ID,  // Use Argon2id for password hashing
        'options' => [
            'memory_cost' => 65536,  // 64 MB
            'time_cost' => 4,
            'threads' => 3,
        ],
    ],
];
