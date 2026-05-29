<div class="notification-icon" id="notif-bell">
    <img src="/app/src/assets/images/notification-bing.svg" alt="Notification Icon">
    <?php if (($unread_count ?? 0) > 0): ?>
    <div class="notification-dot"></div>
    <?php endif; ?>
    <div class="notifications-dropdown" id="notif-dropdown" style="display:none;">
        <div class="notif-header">
            <span>Notifications</span>
            <button id="mark-all-read" class="mark-read-btn">Mark all as read</button>
        </div>
        <?php if (empty($notifications)): ?>
        <div class="notif-empty">No notifications yet.</div>
        <?php else: ?>
        <ul class="notif-list">
            <?php foreach ($notifications as $n): ?>
            <li class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                <div class="notif-message"><?php echo htmlspecialchars($n['message']); ?></div>
                <div class="notif-time"><?php echo date('M d, Y H:i', strtotime($n['created_at'])); ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
