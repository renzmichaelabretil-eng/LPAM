<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function log_activity(string $action, string $entityType, ?int $entityId, string $description): void
{
    $adminId = current_admin()['id'] ?? null;
    db()->prepare('INSERT INTO activity_logs(admin_id,action,entity_type,entity_id,description) VALUES(?,?,?,?,?)')
        ->execute([$adminId, $action, $entityType, $entityId, $description]);
}
