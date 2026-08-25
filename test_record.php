<?php
$_SERVER['REQUEST_URI'] = '/domain-system/admin/appointments/record/1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION['user_role'] = 'doctor'; // Simulate doctor login
$_SESSION['user_name'] = 'Dr. House';
$_SESSION['user_id'] = 1;
require 'public/index.php';

