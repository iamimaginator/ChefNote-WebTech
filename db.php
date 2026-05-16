<?php
// Secure session settings (Good practice for XAMPP)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli('localhost', 'root', '', 'chefnote_db');
if ($conn->connect_error) die('DB connection failed.');

// 1. Create Users Table
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT ''");

// 2. Create Recipes Table with user_id relationship
$conn->query("CREATE TABLE IF NOT EXISTS recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    ingredients TEXT,
    instructions TEXT,
    time VARCHAR(100),
    mood VARCHAR(50) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migration: Add user_id column if it doesn't exist for older tables
$result = $conn->query("SHOW COLUMNS FROM recipes LIKE 'user_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE recipes ADD COLUMN user_id INT NOT NULL AFTER id");
}

$conn->query("ALTER TABLE recipes ADD COLUMN IF NOT EXISTS mood VARCHAR(50) DEFAULT ''");