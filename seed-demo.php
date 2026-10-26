<?php
/**
 * seed-demo.php – CLI-only demo data seeder for LeftoverLink
 *
 * Safety:
 *   - Does NOT drop or reset any tables.
 *   - Inserts demo records only if they do NOT already exist.
 *   - Uses transactions and prepared statements.
 *   - Must be executed from the command line (CLI). If accessed via web, it aborts.
 *   - Checks that the script is run on a local development host (hostname = 'localhost' or IP = 127.0.0.1).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from the command line.');
}

$allowedHosts = ['localhost', '127.0.0.1'];
if (!in_array(gethostname(), $allowedHosts, true) && !in_array($_SERVER['SERVER_ADDR'] ?? '', $allowedHosts, true)) {
    fwrite(STDERR, "Warning: You appear to be running this on a non‑local host. Proceed? (y/N) ");
    $answer = trim(fgets(STDIN));
    if (strtolower($answer) !== 'y') {
        exit("Aborted.\n");
    }
}

require_once __DIR__ . '/config.php';

function fetchColumn(PDO $pdo, string $sql, array $params = []): mixed {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$pdo->beginTransaction();
try {
    // 1. Demo users
    $demoUsers = [
        [
            'name' => 'Demo Donor',
            'email' => 'donor_demo@example.com',
            'password' => 'donorpass',
            'role' => 'donor'
        ],
        [
            'name' => 'Demo Recipient',
            'email' => 'recipient_demo@example.com',
            'password' => 'recipientpass',
            'role' => 'recipient'
        ],
        [
            'name' => 'Demo Admin',
            'email' => 'admin_demo@example.com',
            'password' => 'adminpass',
            'role' => 'admin'
        ]
    ];
    $userIds = [];
    $userInsert = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    foreach ($demoUsers as $u) {
        $existingId = fetchColumn($pdo, 'SELECT id FROM users WHERE email = ?', [$u['email']]);
        if ($existingId) {
            $userIds[$u['email']] = $existingId;
            echo "[INFO] User {$u['email']} already exists (id=$existingId).\n";
            continue;
        }
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $userInsert->execute([$u['name'], $u['email'], $hash, $u['role']]);
        $newId = $pdo->lastInsertId();
        $userIds[$u['email']] = $newId;
        echo "[INSERT] Created {$u['role']} account {$u['email']} (id=$newId).\n";
    }
    // 2. Demo food listings
    $now = new DateTime();
    $future = (clone $now)->modify('+2 days')->format('Y-m-d H:i:s');
    $past = (clone $now)->modify('-2 days')->format('Y-m-d H:i:s');
    $demoListings = [
        [
            'donor' => 'donor_demo@example.com',
            'food_name' => 'Veg Sandwiches',
            'description' => 'Fresh vegetable sandwiches, 2 hrs after preparation',
            'category' => 'Sandwich',
            'quantity' => 30,
            'unit' => 'pieces',
            'location' => 'Canteen A',
            'prep' => 'Prepared at 10:00 AM',
            'deadline' => $future,
            'status' => 'available',
            'available_quantity' => 30,
        ],
        [
            'donor' => 'donor_demo@example.com',
            'food_name' => 'Fruit Box',
            'description' => 'Assorted seasonal fruits, ready to eat',
            'category' => 'Fruit',
            'quantity' => 15,
            'unit' => 'boxes',
            'location' => 'Cafeteria B',
            'prep' => 'Packed this morning',
            'deadline' => $future,
            'status' => 'partially_reserved',
            'available_quantity' => 5,
        ],
        [
            'donor' => 'donor_demo@example.com',
            'food_name' => 'Bread Loaves',
            'description' => 'Whole grain bread, still fresh',
            'category' => 'Bakery',
            'quantity' => 20,
            'unit' => 'loaves',
            'location' => 'Kitchen C',
            'prep' => 'Baked today',
            'deadline' => $future,
            'status' => 'fully_reserved',
            'available_quantity' => 0,
        ],
        [
            'donor' => 'donor_demo@example.com',
            'food_name' => 'Prepared Meal Pack',
            'description' => 'Ready‑to‑heat meals (veg)',
            'category' => 'Meal',
            'quantity' => 25,
            'unit' => 'packs',
            'location' => 'Room 101',
            'prep' => 'Cooked at 8:00 AM',
            'deadline' => $future,
            'status' => 'pending',
            'available_quantity' => 25,
        ],
        [
            'donor' => 'donor_demo@example.com',
            'food_name' => 'Stale Bread',
            'description' => 'Day‑old bread for compost',
            'category' => 'Bakery',
            'quantity' => 10,
            'unit' => 'loaves',
            'location' => 'Backyard',
            'prep' => 'Leftover from yesterday',
            'deadline' => $past,
            'status' => 'expired',
            'available_quantity' => 0,
        ],
        [
            'donor' => 'donor_demo@example.com',
            'food_name' => 'Rejected Salad',
            'description' => 'Salad with expired dressing',
            'category' => 'Salad',
            'quantity' => 8,
            'unit' => 'bowls',
            'location' => 'Prep Area',
            'prep' => 'Mixed earlier today',
            'deadline' => $future,
            'status' => 'rejected',
            'available_quantity' => 0,
        ],
    ];
    $listingInsert = $pdo->prepare('INSERT INTO food_listings (donor_id, food_name, description, category, quantity, unit, location, preparation_details, pickup_deadline, status, available_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $listingIds = [];
    foreach ($demoListings as $l) {
        $donorId = $userIds[$l['donor']];
        $existingId = fetchColumn($pdo, 'SELECT id FROM food_listings WHERE donor_id = ? AND food_name = ? AND pickup_deadline = ?', [$donorId, $l['food_name'], $l['deadline']]);
        if ($existingId) {
            $listingIds[$l['food_name']] = $existingId;
            echo "[INFO] Listing '{$l['food_name']}' already exists (id=$existingId).\n";
            continue;
        }
        $listingInsert->execute([
            $donorId,
            $l['food_name'],
            $l['description'],
            $l['category'],
            $l['quantity'],
            $l['unit'],
            $l['location'],
            $l['prep'],
            $l['deadline'],
            $l['status'],
            $l['available_quantity']
        ]);
        $newId = $pdo->lastInsertId();
        $listingIds[$l['food_name']] = $newId;
        echo "[INSERT] Created listing '{$l['food_name']}' (id=$newId, status={$l['status']}).\n";
    }
    // 3. Demo requests
    $demoRequests = [
        [
            'listing' => 'Veg Sandwiches',
            'recipient' => 'recipient_demo@example.com',
            'quantity_requested' => 5,
            'status' => 'pending',
        ],
        [
            'listing' => 'Fruit Box',
            'recipient' => 'recipient_demo@example.com',
            'quantity_requested' => 5,
            'status' => 'approved',
        ],
        [
            'listing' => 'Bread Loaves',
            'recipient' => 'recipient_demo@example.com',
            'quantity_requested' => 20,
            'status' => 'rejected',
        ],
        [
            'listing' => 'Prepared Meal Pack',
            'recipient' => 'recipient_demo@example.com',
            'quantity_requested' => 10,
            'status' => 'collected',
        ],
        [
            'listing' => 'Veg Sandwiches',
            'recipient' => 'recipient_demo@example.com',
            'quantity_requested' => 2,
            'status' => 'cancelled',
        ],
    ];
    $requestInsert = $pdo->prepare('INSERT INTO food_requests (listing_id, recipient_id, quantity_requested, status) VALUES (?, ?, ?, ?)');
    foreach ($demoRequests as $r) {
        $listingId = $listingIds[$r['listing']] ?? null;
        $recipientId = $userIds[$r['recipient']] ?? null;
        if (!$listingId || !$recipientId) {
            echo "[WARN] Skipping request – unknown listing or recipient ({$r['listing']}).\n";
            continue;
        }
        $exists = fetchColumn($pdo, 'SELECT id FROM food_requests WHERE listing_id = ? AND recipient_id = ? AND quantity_requested = ? AND status = ?', [$listingId, $recipientId, $r['quantity_requested'], $r['status']]);
        if ($exists) {
            echo "[INFO] Request already exists (id=$exists).\n";
            continue;
        }
        $requestInsert->execute([$listingId, $recipientId, $r['quantity_requested'], $r['status']]);
        $newReqId = $pdo->lastInsertId();
        echo "[INSERT] Created request (id=$newReqId) for listing '{$r['listing']}' – status {$r['status']}.\n";
    }
    $pdo->commit();
    echo "\nDemo data seeding completed successfully.\n";
    echo "\n---\nPasswords (keep local only)\nDonor: donor_demo@example.com / donorpass\nRecipient: recipient_demo@example.com / recipientpass\nAdmin: admin_demo@example.com / adminpass\n---\n";
} catch (Exception $e) {
    $pdo->rollBack();
    fwrite(STDERR, "[ERROR] Seeding failed: " . $e->getMessage() . "\n");
    exit(1);
}
?>
