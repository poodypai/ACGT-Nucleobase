<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

$stmt = $db->prepare(
    'SELECT * FROM nucleotide_records WHERE uploaded_by = ? ORDER BY created_at DESC'
);
$stmt->execute([$user['id']]);
$myRecords = $stmt->fetchAll();

$logStmt = $db->prepare(
    "SELECT l.*, r.accession_number
     FROM activity_log l
     LEFT JOIN nucleotide_records r ON r.id = l.record_id
     WHERE l.user_id = ?
     ORDER BY l.created_at DESC
     LIMIT 15"
);
$logStmt->execute([$user['id']]);
$myActivity = $logStmt->fetchAll();

$actionColors = [
    'CREATE'   => 'bg-emerald-50 text-emerald-600',
    'UPDATE'   => 'bg-amber-50 text-amber-600',
    'DELETE'   => 'bg-red-50 text-red-600',
    'DOWNLOAD' => 'bg-slate-100 text-slate-500',
];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<h1 class="font-display font-bold text-2xl text-slate-900 mb-1">Welcome, <?= h($user['full_name']) ?></h1>
<p class="text-slate-500 mb-8">Manage the records you've contributed and review your recent activity.</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  <div class="lg:col-span-2">
    <div class="flex items-center justify-between mb-3">
      <h2 class="font-display font-semibold text-lg text-slate-800">My records (<?= count($myRecords) ?>)</h2>
      <a href="upload.php" class="text-sm font-medium text-teal-600 hover:underline">+ Upload new</a>
    </div>

    <?php if (!$myRecords): ?>
      <div class="text-center py-12 border border-dashed border-slate-300 rounded-xl text-slate-500 text-sm">
        You haven't uploaded any records yet. <a href="upload.php" class="text-teal-600 hover:underline">Upload your first FASTA file</a>.
      </div>
    <?php else: ?>
      <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm divide-y divide-slate-100">
        <?php foreach ($myRecords as $r): ?>
          <div class="flex items-center justify-between px-4 py-3 bg-white hover:bg-slate-50 transition">
            <div>
              <a href="view.php?id=<?= (int) $r['id'] ?>" class="font-mono text-teal-700 font-medium hover:underline"><?= h($r['accession_number']) ?></a>
              <p class="text-xs text-slate-500"><?= h($r['organism']) ?: 'Unspecified organism' ?> &middot; <?= number_format((int) $r['sequence_length']) ?> bp &middot; <?= h(date('M j, Y', strtotime($r['created_at']))) ?></p>
            </div>
            <div class="flex gap-3 text-sm">
              <a href="edit.php?id=<?= (int) $r['id'] ?>" class="text-slate-500 hover:text-teal-600">Edit</a>
              <a href="delete.php?id=<?= (int) $r['id'] ?>" class="text-slate-500 hover:text-red-600">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <h2 class="font-display font-semibold text-lg text-slate-800 mb-3">Recent activity</h2>
    <?php if (!$myActivity): ?>
      <p class="text-sm text-slate-500">No activity yet.</p>
    <?php else: ?>
      <ul class="space-y-2">
        <?php foreach ($myActivity as $log): ?>
          <li class="flex items-start gap-2 text-sm bg-white border border-slate-200 rounded-lg px-3 py-2">
            <span class="px-1.5 py-0.5 rounded text-xs font-mono shrink-0 <?= $actionColors[$log['action']] ?? 'bg-slate-100 text-slate-500' ?>"><?= h($log['action']) ?></span>
            <span class="text-slate-600">
              <span class="font-mono text-slate-800"><?= h($log['accession_number'] ?? $log['details']) ?></span>
              <span class="block text-xs text-slate-400"><?= h(date('M j, g:ia', strtotime($log['created_at']))) ?></span>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
