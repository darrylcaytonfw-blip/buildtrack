<?php
if (!isset($conn)) {
    include 'database.php';
}

if (isset($_POST['mark_read'])) {
    $id = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1");
}

$notifications = $conn->query("
    SELECT *
    FROM notifications
    ORDER BY is_read ASC, created_at DESC, id DESC
");
?>

<style>
    .notifications-wrap {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        color: #0f172a;
        overflow: hidden;
    }

    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .notification-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 16px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f7;
    }

    .notification-row.unread {
        background: #f8fbff;
    }

    .notification-title {
        font-weight: 800;
        color: #0b1f3a;
    }

    .notification-message {
        color: #475569;
        margin: 4px 0 6px;
    }

    .notification-time {
        color: #64748b;
        font-size: .82rem;
    }

    @media (max-width: 767px) {
        .notifications-header,
        .notification-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white fw-bold mb-1">Notifications</h2>
        <small class="text-secondary">System alerts and workflow updates</small>
    </div>
</div>

<div class="notifications-wrap">
    <div class="notifications-header">
        <div>
            <h5 class="fw-bold mb-1">Notification Center</h5>
            <small class="text-secondary">Newest unread notifications are shown first.</small>
        </div>
        <form method="post">
            <button name="mark_all_read" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-check2-all"></i> Mark all read
            </button>
        </form>
    </div>

    <?php if ($notifications && $notifications->num_rows): ?>
        <?php while ($row = $notifications->fetch_assoc()): ?>
            <div class="notification-row <?= (int)$row['is_read'] === 0 ? 'unread' : '' ?>">
                <div>
                    <div class="notification-title">
                        <?= htmlspecialchars($row['title'] ?? 'Notification', ENT_QUOTES, 'UTF-8') ?>
                        <?php if ((int)$row['is_read'] === 0): ?>
                            <span class="badge text-bg-primary ms-2">New</span>
                        <?php endif; ?>
                    </div>
                    <div class="notification-message">
                        <?= nl2br(htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                    <div class="notification-time">
                        <?= htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-start">
                    <?php if (!empty($row['link_page'])): ?>
                        <a class="btn btn-sm btn-gold" href="./?link=<?= urlencode($row['link_page']) ?>">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ((int)$row['is_read'] === 0): ?>
                        <form method="post">
                            <input type="hidden" name="notification_id" value="<?= (int)$row['id'] ?>">
                            <button name="mark_read" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="p-4 text-center text-muted">No notifications yet.</div>
    <?php endif; ?>
</div>
