<?php
// public/dashboard.php
// Public entry point for Manager Dashboard

// No auth requirement
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/controllers/NreListController.php';

// Initialize Controller
$listController = new NreListController();

// Fetch ALL NREs (Simulating Admin view with userID 0)
// $userId = 0, $isAdmin = true -> returns all records
$nres = $listController->listNres(0, true, true, null, 50, 0); // Limit 50 for performance

// Load Templates
require_once __DIR__ . '/../templates/dashboard/header.php';
require_once __DIR__ . '/../templates/dashboard/view.php';
