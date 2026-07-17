<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------------------------------------------------------- *
 *  Auth helpers
 * ---------------------------------------------------------------- */

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
    ];
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        header('Location: login.php');
        exit;
    }
}

/* ---------------------------------------------------------------- *
 *  Flash messages (one-time session notices)
 * ---------------------------------------------------------------- */

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ---------------------------------------------------------------- *
 *  Output helpers
 * ---------------------------------------------------------------- */

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---------------------------------------------------------------- *
 *  FASTA parsing
 *
 *  A FASTA file can contain one or many records:
 *    >accession description text
 *    ACGTACGT...
 *    ACGT...
 *    >next_accession ...
 *    ...
 * ---------------------------------------------------------------- */

function parseFasta(string $content): array
{
    $records = [];
    $lines = preg_split('/\r\n|\r|\n/', trim($content));

    $current = null;

    foreach ($lines as $line) {
        $line = rtrim($line);
        if ($line === '') {
            continue;
        }

        if ($line[0] === '>') {
            if ($current !== null) {
                $records[] = $current;
            }
            $header = trim(substr($line, 1));
            // First whitespace-delimited token is the accession/ID,
            // the remainder (if any) is treated as a description.
            $parts = preg_split('/\s+/', $header, 2);
            $current = [
                'accession'   => $parts[0] !== '' ? $parts[0] : ('SEQ_' . (count($records) + 1)),
                'description' => $parts[1] ?? '',
                'sequence'    => '',
            ];
        } else {
            if ($current === null) {
                // Sequence data with no header line — start an anonymous record.
                $current = [
                    'accession'   => 'SEQ_' . (count($records) + 1),
                    'description' => '',
                    'sequence'    => '',
                ];
            }
            $current['sequence'] .= preg_replace('/\s+/', '', $line);
        }
    }

    if ($current !== null) {
        $records[] = $current;
    }

    return $records;
}

/**
 * Very small validator: nucleotide sequences should only contain
 * IUPAC nucleotide codes (ACGTU + ambiguity codes + gap/N).
 */
function isValidNucleotideSequence(string $sequence): bool
{
    return $sequence !== '' && preg_match('/^[ACGTUNRYSWKMBDHV\-]+$/i', $sequence) === 1;
}

function detectSequenceType(string $sequence): string
{
    // If it contains U but no T, treat it as RNA.
    $hasU = stripos($sequence, 'U') !== false;
    $hasT = stripos($sequence, 'T') !== false;
    return ($hasU && !$hasT) ? 'RNA' : 'DNA';
}

function calculateGcContent(string $sequence): float
{
    $length = strlen($sequence);
    if ($length === 0) {
        return 0.0;
    }
    $gc = preg_match_all('/[GCgc]/', $sequence);
    return round(($gc / $length) * 100, 2);
}

/**
 * Wrap a raw sequence into fixed-width FASTA lines (default 70 chars,
 * the NCBI convention) for file downloads / display.
 */
function wrapSequence(string $sequence, int $width = 70): string
{
    return trim(chunk_split($sequence, $width, "\n"));
}

function buildFastaFile(string $accession, string $description, string $sequence): string
{
    $header = '>' . $accession . ($description !== '' ? ' ' . $description : '');
    return $header . "\n" . wrapSequence($sequence) . "\n";
}

/* ---------------------------------------------------------------- *
 *  Colorized sequence rendering (the signature visual element)
 *  Each base gets a color-coded <span> so users can visually scan
 *  a sequence the way a genome browser would render it.
 * ---------------------------------------------------------------- */

function renderColoredSequence(string $sequence, ?int $limit = null, int $lineWidth = 60): string
{
    $seq = $limit !== null ? substr($sequence, 0, $limit) : $sequence;
    $map = ['A' => 'seq-a', 'T' => 'seq-t', 'U' => 'seq-u', 'G' => 'seq-g', 'C' => 'seq-c'];

    $out = '';
    $len = strlen($seq);
    for ($i = 0; $i < $len; $i++) {
        $base = strtoupper($seq[$i]);
        $class = $map[$base] ?? 'seq-n';
        $out .= '<span class="' . $class . '">' . h($seq[$i]) . '</span>';
        if (($i + 1) % $lineWidth === 0) {
            $out .= "\n";
        }
    }

    if ($limit !== null && $limit < strlen($sequence)) {
        $out .= '<span class="text-slate-400">&hellip;</span>';
    }

    return $out;
}

/* ---------------------------------------------------------------- *
 *  Activity log
 * ---------------------------------------------------------------- */

function logActivity(?int $userId, ?int $recordId, string $action, string $details = ''): void
{
    $stmt = getDB()->prepare(
        'INSERT INTO activity_log (user_id, record_id, action, details) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $recordId, $action, $details]);
}

/* ---------------------------------------------------------------- *
 *  Pagination helper
 * ---------------------------------------------------------------- */

function currentPage(): int
{
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    return $page > 0 ? $page : 1;
}
