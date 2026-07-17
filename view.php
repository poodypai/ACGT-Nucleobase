<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = getDB()->prepare(
    'SELECT r.*, u.username AS uploader, u.full_name AS uploader_name
     FROM nucleotide_records r JOIN users u ON u.id = r.uploaded_by
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    setFlash('error', 'That record does not exist.');
    header('Location: index.php');
    exit;
}

$user = currentUser();
$pageTitle = $record['accession_number'];
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
  <a href="index.php" class="text-sm text-slate-500 hover:text-teal-600">&larr; Back to browse</a>
</div>

<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
  <div class="border-b border-slate-100 px-6 py-5 flex flex-wrap items-start justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <h1 class="font-mono font-semibold text-2xl text-slate-900"><?= h($record['accession_number']) ?></h1>
        <span class="px-2 py-0.5 rounded text-xs font-mono <?= $record['sequence_type'] === 'RNA' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600' ?>"><?= h($record['sequence_type']) ?></span>
      </div>
      <p class="text-slate-500 italic"><?= h($record['organism']) ?: 'Organism not specified' ?><?= $record['gene_name'] ? ' &middot; ' . h($record['gene_name']) : '' ?></p>
    </div>
    <div class="flex gap-2">
      <a href="download.php?id=<?= (int) $record['id'] ?>"
         class="px-4 py-2 text-sm bg-teal-600 hover:bg-teal-500 text-white rounded-lg font-medium transition">Download FASTA</a>
      <?php if ($user): ?>
        <a href="edit.php?id=<?= (int) $record['id'] ?>"
           class="px-4 py-2 text-sm border border-slate-300 hover:border-teal-500 hover:text-teal-600 rounded-lg font-medium transition">Edit</a>
        <a href="delete.php?id=<?= (int) $record['id'] ?>"
           onclick="return confirm('Delete this record permanently? This cannot be undone.');"
           class="px-4 py-2 text-sm border border-red-200 text-red-600 hover:bg-red-50 rounded-lg font-medium transition">Delete</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-slate-100 border-b border-slate-100 text-center">
    <div class="px-4 py-4">
      <p class="text-xs text-slate-400 uppercase tracking-wide">Length</p>
      <p class="font-mono font-semibold text-lg text-slate-800"><?= number_format((int) $record['sequence_length']) ?> bp</p>
    </div>
    <div class="px-4 py-4">
      <p class="text-xs text-slate-400 uppercase tracking-wide">GC content</p>
      <p class="font-mono font-semibold text-lg text-slate-800"><?= h((string) $record['gc_content']) ?>%</p>
    </div>
    <div class="px-4 py-4">
      <p class="text-xs text-slate-400 uppercase tracking-wide">Uploaded by</p>
      <p class="font-medium text-slate-800"><?= h($record['uploader']) ?></p>
    </div>
    <div class="px-4 py-4">
      <p class="text-xs text-slate-400 uppercase tracking-wide">Last updated</p>
      <p class="font-medium text-slate-800"><?= h(date('M j, Y', strtotime($record['updated_at']))) ?></p>
    </div>
  </div>

  <?php if ($record['description']): ?>
    <div class="px-6 py-4 border-b border-slate-100">
      <p class="text-xs text-slate-400 uppercase tracking-wide mb-1">Description</p>
      <p class="text-slate-700"><?= h($record['description']) ?></p>
    </div>
  <?php endif; ?>

  <div class="px-6 py-5">
    <div class="flex items-center justify-between mb-2">
      <p class="text-xs text-slate-400 uppercase tracking-wide">Sequence</p>
      <div class="flex gap-3 text-xs font-mono">
        <span class="text-red-400">&#9679; A</span>
        <span class="text-blue-400">&#9679; <?= $record['sequence_type'] === 'RNA' ? 'U' : 'T' ?></span>
        <span class="text-green-400">&#9679; G</span>
        <span class="text-amber-400">&#9679; C</span>
      </div>
    </div>
    <pre class="bg-ink text-slate-200 rounded-lg p-4 overflow-x-auto text-sm leading-relaxed font-mono whitespace-pre-wrap break-all"><?= renderColoredSequence($record['sequence']) ?></pre>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
