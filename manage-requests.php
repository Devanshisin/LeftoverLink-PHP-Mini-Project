<?php
// manage-requests.php – Donor manages requests for their listings
require_once __DIR__ . '/auth.php';
requireLogin();
requireRole(['donor']);
$user = currentUser();

// Process POST actions: approve, reject, collect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $msg = 'Invalid CSRF token.';
    } else {
        $requestId = (int)$_POST['request_id'];
        $action = $_POST['action'];
        // Fetch request + listing to verify ownership and status
        $stmt = $pdo->prepare('SELECT fr.*, fl.available_quantity, fl.unit FROM food_requests fr JOIN food_listings fl ON fr.listing_id = fl.id WHERE fr.id = ? AND fl.donor_id = ?');
        $stmt->execute([$requestId, $user['id']]);
        $req = $stmt->fetch();
        if (!$req) {
            $msg = 'Request not found or you do not have permission.';
        } else {
            if ($action === 'approve' && $req['status'] === 'pending') {
                // Transaction to reserve quantity
                $pdo->beginTransaction();
                try {
                    // Ensure enough quantity
                    if ($req['quantity_requested'] > $req['available_quantity']) {
                        throw new Exception('Not enough available quantity to approve.');
                    }
                    // Update request status
                    $updReq = $pdo->prepare('UPDATE food_requests SET status = ? WHERE id = ?');
                    $updReq->execute(['approved', $requestId]);
                    // Reduce available_quantity
                    $newAvail = $req['available_quantity'] - $req['quantity_requested'];
                    $updListing = $pdo->prepare('UPDATE food_listings SET available_quantity = ?, status = ? WHERE id = ?');
                    $newStatus = ($newAvail == 0) ? 'fully_reserved' : 'partially_reserved';
                    $updListing->execute([$newAvail, $newStatus, $req['listing_id']]);
                    $pdo->commit();
                    $msg = 'Request approved.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $msg = $e->getMessage();
                }
            } elseif ($action === 'reject' && $req['status'] === 'pending') {
                $stmt = $pdo->prepare('UPDATE food_requests SET status = ? WHERE id = ?');
                $stmt->execute(['rejected', $requestId]);
                $msg = 'Request rejected.';
            } elseif ($action === 'collect' && $req['status'] === 'approved') {
                // Mark as collected and update listing status if needed
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('UPDATE food_requests SET status = ? WHERE id = ?');
                    $stmt->execute(['collected', $requestId]);
                    // If all quantity has been collected, set listing status to collected
                    $stmt = $pdo->prepare('SELECT SUM(quantity_requested) AS total_approved FROM food_requests WHERE listing_id = ? AND status = ?');
                    $stmt->execute([$req['listing_id'], 'approved']);
                    $approvedSum = $stmt->fetchColumn();
                    // If no more approved requests remain, set listing to collected
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM food_requests WHERE listing_id = ? AND status = ?');
                    $stmt->execute([$req['listing_id'], 'approved']);
                    $remainingApproved = $stmt->fetchColumn();
                    if ($remainingApproved == 0) {
                        $stmt = $pdo->prepare('UPDATE food_listings SET status = ? WHERE id = ?');
                        $stmt->execute(['collected', $req['listing_id']]);
                    }
                    $pdo->commit();
                    $msg = 'Request marked as collected.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $msg = $e->getMessage();
                }
            } else {
                $msg = 'Invalid action or request state.';
            }
        }
    }
}
$csrf = generateCsrfToken();

// Fetch all requests for donor's listings
$stmt = $pdo->prepare("SELECT fr.id, fr.quantity_requested, fr.status, fr.created_at, fl.food_name, fl.unit FROM food_requests fr JOIN food_listings fl ON fr.listing_id = fl.id WHERE fl.donor_id = ? ORDER BY fr.created_at DESC");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Requests – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Manage Requests for Your Listings</h2>
    <?php if (!empty($msg)): ?>
        <div class="alert <?= strpos($msg, 'error') !== false ? 'error' : 'success' ?>"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if (empty($requests)): ?>
        <p>No requests have been made for your listings yet.</p>
    <?php else: ?>
        <table class="listings-table">
            <thead>
                <tr>
                    <th>Food</th>
                    <th>Quantity Requested</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= e($r['food_name']) ?></td>
                        <td><?= e($r['quantity_requested']) . ' ' . e($r['unit']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', $r['status']))) ?></td>
                        <td><?= e($r['created_at']) ?></td>
                        <td>
                            <?php if ($r['status'] === 'pending'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn-primary">Approve</button>
                                </form>
                                <form method="post" style="display:inline; margin-left:5px;">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn-danger">Reject</button>
                                </form>
                            <?php elseif ($r['status'] === 'approved'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="action" value="collect" class="btn-success">Mark Collected</button>
                                </form>
                            <?php else: ?>
                                <span class="badge">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
