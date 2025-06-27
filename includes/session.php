<?php
session_start();

// Set session timeout to 24 hours
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();
}

// Check session timeout
function checkSessionTimeout() {
    $timeout = 86400; // 24 hours in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
} 