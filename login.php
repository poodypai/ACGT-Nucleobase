<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($oldUsername === '' || $password === '') {
        $errors[] = 'Please enter both username and password.';
    } else {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$oldUsername, $oldUsername]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Incorrect username or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];

            setFlash('success', 'Welcome back, ' . $user['full_name'] . '.');
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto">
  <h1 class="font-display font-bold text-2xl text-slate-900 mb-1">Log in</h1>
  <p class="text-slate-500 text-sm mb-6">Access your account to upload, edit, and delete records.</p>

  <?php if ($errors): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <ul class="list-disc list-inside space-y-1">
        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 shadow-sm">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Username or email</label>
      <input type="text" name="username" value="<?= h($oldUsername) ?>" required autofocus
             class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
      <input type="password" name="password" required
             class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
    </div>
    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-medium rounded-lg py-2.5 transition">
      Log in
    </button>
  </form>

  <p class="text-sm text-slate-500 mt-4">
    Demo account: <span class="font-mono text-slate-700">demo</span> / <span class="font-mono text-slate-700">demo1234</span>
  </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
