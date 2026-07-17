<?php
require_once __DIR__ . '/functions.php';
$user = currentUser();
$flashes = getFlashes();
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' · NucleoBase' : 'NucleoBase' ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ink:   '#0B1220',
          panel: '#111B2E',
          line:  '#22314D',
          teal:  { 400:'#2DD4BF', 500:'#14B8A6', 600:'#0D9488', 700:'#0F766E' },
          base:  {
            a: '#F87171', /* Adenine  */
            t: '#60A5FA', /* Thymine  */
            u: '#60A5FA', /* Uracil (RNA) */
            g: '#4ADE80', /* Guanine  */
            c: '#FBBF24'  /* Cytosine */
          }
        },
        fontFamily: {
          display: ['"Space Grotesk"', 'sans-serif'],
          body: ['"Inter"', 'sans-serif'],
          mono: ['"IBM Plex Mono"', 'monospace']
        }
      }
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', sans-serif; }
  .font-display { font-family: 'Space Grotesk', sans-serif; }
  .font-mono { font-family: 'IBM Plex Mono', monospace; }
  ::selection { background: #0D9488; color: #F8FAFC; }
  .seq-a { color: #F87171; } .seq-t, .seq-u { color: #60A5FA; }
  .seq-g { color: #4ADE80; } .seq-c { color: #FBBF24; } .seq-n { color: #94A3B8; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

<header class="bg-ink text-slate-200 border-b border-line">
  <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2 group">
      <span class="font-mono text-teal-400 text-lg leading-none tracking-tight">&gt;_ACGT</span>
      <span class="font-display font-700 text-xl tracking-tight text-white">NucleoBase</span>
    </a>
    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
      <?php if ($user): ?>
        <a href="upload.php" class="px-3 py-2 rounded-md transition <?= $activeNav==='upload' ? 'bg-panel text-teal-400' : 'text-slate-300 hover:text-white hover:bg-panel' ?>">Upload</a>
        <a href="dashboard.php" class="px-3 py-2 rounded-md transition <?= $activeNav==='dashboard' ? 'bg-panel text-teal-400' : 'text-slate-300 hover:text-white hover:bg-panel' ?>">Dashboard</a>
      <?php endif; ?>
    </nav>
    <div class="flex items-center gap-3 text-sm">
      <?php if ($user): ?>
        <span class="hidden sm:inline text-slate-400">Signed in as <span class="text-slate-200 font-medium"><?= h($user['username']) ?></span></span>
        <a href="logout.php" class="px-3 py-2 rounded-md bg-panel border border-line hover:border-teal-600 hover:text-teal-400 transition">Log out</a>
      <?php else: ?>
        <a href="login.php" class="px-3 py-2 rounded-md bg-teal-600 text-white hover:bg-teal-500 transition">Log in</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="flex-1">
  <div class="max-w-6xl mx-auto px-6 py-8 w-full">
    <?php foreach ($flashes as $flash): ?>
      <div class="mb-4 rounded-lg border px-4 py-3 text-sm font-medium <?= $flash['type'] === 'error'
          ? 'bg-red-50 border-red-200 text-red-700'
          : 'bg-teal-50 border-teal-200 text-teal-700' ?>">
        <?= h($flash['message']) ?>
      </div>
    <?php endforeach; ?>
