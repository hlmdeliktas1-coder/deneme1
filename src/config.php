<?php
/**
 * src/config.php
 * Central config for the project. Use getConfig() to retrieve.
 */

function getConfig(): array {
    return [
        // App display settings
        'app' => [
            'name' => 'Device Manager',
            'session_name' => 'netpanel_sess',
            'base_path' => '/', // if your project is in a subfolder, change to '/subfolder/'
        ],

        // Database connection (update for your environment)
        'db' => [
            'dsn'  => 'mysql:host=127.0.0.1;dbname=network_panel;charset=utf8mb4',
            'user' => 'root',
            'pass' => '',
            // optional PDO options could be added here
        ],
    ];
}
