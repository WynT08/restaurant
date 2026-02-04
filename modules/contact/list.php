
<?php
// modules/contact/list.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Chỉ cho phép admin truy cập
requirePermission('admin');

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM contact ORDER BY created_at DESC");
$stmt->execute();
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="main-content">
    <h2>Danh sách liên hệ khách hàng</h2>
    <div class="contact-list-grid">
        <?php foreach ($contacts as $c): ?>
        <div class="contact-card card">
            <div class="card-body">
                <div class="contact-card-header">
                    <span class="contact-id">#<?= htmlspecialchars($c['id']) ?></span>
                    <span class="contact-status badge badge-<?= $c['status'] === 'Đã phản hồi' ? 'success' : ($c['status'] === 'Đang xử lý' ? 'warning' : 'secondary') ?>">
                        <?= htmlspecialchars($c['status']) ?>
                    </span>
                </div>
                <h5 class="contact-name"> <?= htmlspecialchars($c['name']) ?> </h5>
                <div class="contact-info">
                    <span><i class="fa fa-phone"></i> <?= htmlspecialchars($c['phone']) ?></span>
                    <span><i class="fa fa-envelope"></i> <?= htmlspecialchars($c['email']) ?></span>
                </div>
                <div class="contact-meta">
                    <span class="contact-subject"><strong>Chủ đề:</strong> <?= htmlspecialchars($c['subject']) ?></span>
                    <span class="contact-date"><i class="fa fa-clock"></i> <?= htmlspecialchars($c['created_at']) ?></span>
                </div>
                <div class="contact-message">
                    <strong>Nội dung:</strong><br>
                    <?= nl2br(htmlspecialchars($c['message'])) ?>
                </div>
                <?php if (!empty($c['response'])): ?>
                <div class="contact-response">
                    <strong>Phản hồi:</strong><br>
                    <?= nl2br(htmlspecialchars($c['response'])) ?>
                </div>
                <?php endif; ?>
                <div class="contact-actions">
                    <a href="reply.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Phản hồi</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($contacts)): ?>
            <div class="alert alert-info">Chưa có liên hệ nào.</div>
        <?php endif; ?>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
