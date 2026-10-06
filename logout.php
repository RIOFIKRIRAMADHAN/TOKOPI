<?php
require_once __DIR__ . '/config/bootstrap.php';

// Reset active outlet sebelum logout
unset($_SESSION['active_outlet_id']);

session_unset();
session_destroy();

redirect('/tokopi/login.php');