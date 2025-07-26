<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id || !is_numeric($transaction_id)) {
    header('Location: transactions.php?error=Invalid+transaction+ID');
    exit();
}
$stmt = $conn->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $transaction_id, $user_id);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();
    header('Location: transactions.php?deleted=1');
    exit();
} else {
    $stmt->close();
    header('Location: transactions.php?error=Could+not+delete+transaction');
    exit();
} 