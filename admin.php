<?php
// Compatibility shim — redirects to the real admin dashboard
require 'config.php';
requireAdmin();
header('Location: admin_dashboard.php');
exit;