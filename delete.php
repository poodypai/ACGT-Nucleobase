<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM nucleotide_records WHERE id = ?');
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    setFlash('error', 'That record does not exist.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $del = $db->prepare('DELETE FROM nucleotide_records WHERE id = ?');
    $del->execute([$id]);

    logActivity($user['id'], $id, 'DELETE', $record['accession_number']);
    setFlash('success', 'Record "' . $record['accession_number'] . '" was deleted.');
    header('Location: index.php');
    exit;
}

// GET request: show a confirmation screen (avoids destructive actions via a plain link click).
$pageTitle = 'Delete ' . $record['accession_number'];
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-lg mx-auto text-center bg-white border border-red-200 rounded-xl p-8 shadow-sm">
  <p class="font-mono text-red-500 text-sm mb-2">&gt;_ confirm deletion</p>
  <h1 class="font-display font-bold text-xl text-slate-900 mb-2">
    Delete <span class="font-mono"><?= h($record['accession_number']) ?></span>?
  </h1>
  <p class="text-slate-500 text-sm mb-6">This will permanently remove the record and its sequence data from the database. This action cannot be undone.</p>

  <form method="post" class="flex justify-center gap-3">
    <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
    <a href="view.php?id=<?= (int) $record['id'] ?>" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">Cancel</a>
    <button type="submit" class="px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-500 text-white font-medium">Yes, delete permanently</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
