<?php
/**
 * StudentHub Portal - Secure Sign Out
 * Practical 10: Session Invalidation and Secure Cookie Cleanup
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/includes/auth.php';

logout_user();

header('Location: Login.php?msg=logged_out');
exit;
