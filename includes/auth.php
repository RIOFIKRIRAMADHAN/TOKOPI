<?php
// includes/auth.php

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_name() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function current_user_role() {
    return $_SESSION['role'] ?? null;
}

function current_tenant_id() {
    return $_SESSION['tenant_id'] ?? null;
}

function current_outlet_id() {
    // Owner bisa punya active_outlet_id yang bisa diganti-ganti
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner') {
        return $_SESSION['active_outlet_id'] ?? ($_SESSION['outlet_id'] ?? null);
    }
    return $_SESSION['outlet_id'] ?? null;
}

function set_active_outlet($outlet_id) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner') {
        $_SESSION['active_outlet_id'] = (int)$outlet_id;
    }
}

function is_owner() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/tokopi/login.php');
    }
}

function require_role($roles) {
    require_login();
    if (is_string($roles)) $roles = [$roles];
    if (!in_array(current_user_role(), $roles, true)) {
        http_response_code(403);
        die('Akses ditolak.');
    }
}

function is_role($role): bool {
    return current_user_role() === $role;
}
