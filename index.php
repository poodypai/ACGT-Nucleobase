<?php
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$search      = trim($_GET['q'] ?? '');
$organism    = trim($_GET['organism'] ?? '');
$seqType     = trim($_GET['type'] ?? '');
$perPage     = 10;
$page        = currentPage();
$offset      = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(accession_number LIKE ? OR gene_name LIKE ? OR organism LIKE ? OR description LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($organism !== '') {
    $where[] = 'organism = ?';
    $params[] = $organism;
}
if ($seqType === 'DNA' || $seqType === 'RNA') {
    $where[] = 'sequence_type = ?';
    $params[] = $seqType;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM nucleotide_records $whereSql");
$countStmt->execute($params);
$totalRecords = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRecords / $perPage));

$sql = "SELECT r.*, u.username AS uploader
        FROM nucleotide_records r
        JOIN users u ON u.id = r.uploaded_by
        $whereSql
        ORDER BY r.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$organismList = $db->query('SELECT DISTINCT organism FROM nucleotide_records WHERE organism != "" ORDER BY organism')
                    ->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Browse records';
$activeNav = 'browse';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-8">
  <h1 class="font-display font-bold text-3xl text-slate-900">Browse nucleotide records</h1>
  <p class="text-slate-500 mt-1">Search, filter, and inspect sequences stored in the database. <span class="font-mono text-teal-600"><?= $totalRecords ?></span> record<?= $totalRecords === 1 ? '' : 's' ?> total.</p>
</div>

<form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-8 bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
  <div class="md:col-span-2">
    <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Accession, gene, organism, keyword&hellip;"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
  </div>
  <div>
    <label class="block text-xs font-medium text-slate-500 mb-1">Organism</label>
    <select name="organism" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
      <option value="">All organisms</option>
      <?php foreach ($organismList as $o): ?>
        <option value="<?= h($o) ?>" <?= $organism === $o ? 'selected' : '' ?>><?= h($o) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
    <div class="flex gap-2">
      <select name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">DNA + RNA</option>
        <option value="DNA" <?= $seqType === 'DNA' ? 'selected' : '' ?>>DNA</option>
        <option value="RNA" <?= $seqType === 'RNA' ? 'selected' : '' ?>>RNA</option>
      </select>
    </div>
  </div>
  <div class="md:col-span-4 flex justify-end gap-2">
    <a href="index.php" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700">Reset</a>
    <button type="submit" class="px-4 py-2 text-sm bg-teal-600 hover:bg-teal-500 text-white rounded-lg font-medium transition">Apply filters</button>
  </div>
</form>

<?php if (!$records): ?>
  <div class="text-center py-16 border border-dashed border-slate-300 rounded-xl">
    <p class="font-mono text-teal-600 text-lg mb-1">&gt;_ no matches</p>
    <p class="text-slate-500 text-sm">Try a different search term, or clear your filters.</p>
  </div>
<?php else: ?>
  <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
        <tr>
          <th class="px-4 py-3 text-left">Accession</th>
          <th class="px-4 py-3 text-left">Organism</th>
          <th class="px-4 py-3 text-left">Gene</th>
          <th class="px-4 py-3 text-left">Type</th>
          <th class="px-4 py-3 text-left">Length</th>
          <th class="px-4 py-3 text-left">GC%</th>
          <th class="px-4 py-3 text-left">Uploaded by</th>
          <th class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 bg-white">
        <?php foreach ($records as $r): ?>
          <tr class="hover:bg-slate-50 transition">
            <td class="px-4 py-3">
              <a href="view.php?id=<?= (int) $r['id'] ?>" class="font-mono text-teal-700 font-medium hover:underline"><?= h($r['accession_number']) ?></a>
            </td>
            <td class="px-4 py-3 text-slate-600 italic"><?= h($r['organism']) ?: '&mdash;' ?></td>
            <td class="px-4 py-3 text-slate-600"><?= h($r['gene_name']) ?: '&mdash;' ?></td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded text-xs font-mono <?= $r['sequence_type'] === 'RNA' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600' ?>"><?= h($r['sequence_type']) ?></span>
            </td>
            <td class="px-4 py-3 font-mono text-slate-500"><?= number_format((int) $r['sequence_length']) ?> bp</td>
            <td class="px-4 py-3 font-mono text-slate-500"><?= h((string) $r['gc_content']) ?>%</td>
            <td class="px-4 py-3 text-slate-500"><?= h($r['uploader']) ?></td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <a href="view.php?id=<?= (int) $r['id'] ?>" class="text-slate-500 hover:text-teal-600 mr-3">View</a>
              <a href="download.php?id=<?= (int) $r['id'] ?>" class="text-slate-500 hover:text-teal-600">Download</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between mt-6 text-sm">
      <p class="text-slate-500">Page <?= $page ?> of <?= $totalPages ?></p>
      <div class="flex gap-2">
        <?php
          $qs = $_GET;
          if ($page > 1):
            $qs['page'] = $page - 1;
        ?>
          <a href="?<?= h(http_build_query($qs)) ?>" class="px-3 py-1.5 rounded-lg border border-slate-300 hover:border-teal-500 hover:text-teal-600">Previous</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): $qs['page'] = $page + 1; ?>
          <a href="?<?= h(http_build_query($qs)) ?>" class="px-3 py-1.5 rounded-lg border border-slate-300 hover:border-teal-500 hover:text-teal-600">Next</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
