<?php
// Purge transactions soft-deleted more than 30 days ago.
// Run from CLI or via cron/Task Scheduler (php purge_old_trash.php)
include 'config.php';
if (php_sapi_name() !== 'cli') {
    echo "This script is intended to be run from the command line.\n";
}
$days = 30;
$stmt = $conn->prepare("DELETE FROM transactions WHERE deleted_at IS NOT NULL AND deleted_at < (NOW() - INTERVAL ? DAY)");
$stmt->bind_param('i', $days);
if ($stmt->execute()) {
    $count = $stmt->affected_rows;
    echo "Purged $count transactions older than $days days.\n";
} else {
    echo "Purge failed.\n";
}
$stmt->close();
?>
