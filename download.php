<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = getDB()->prepare('SELECT * FROM nucleotide_records WHERE id = ?');
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    setFlash('error', 'That record does not exist.');
    header('Location: index.php');
    exit;
}

$fasta = buildFastaFile($record['accession_number'], $record['description'], $record['sequence']);
$filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $record['accession_number']) . '.fasta';

$user = currentUser();
logActivity($user['id'] ?? null, $record['id'], 'DOWNLOAD', $record['accession_number']);

header('Content-Type: text/x-fasta; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($fasta));
echo $fasta;
exit;
