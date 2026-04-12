<?php

declare(strict_types=1);

use App\Lang\Lang;

$lang = Lang::current();
$bp = base_path();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <title>Admin · Pedidos · Tarumba's Farm</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bangers&display=swap" rel="stylesheet">
  <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&display=swap" rel="stylesheet">
  <style>
    :root {
      --tf-bg: #050505;
      --tf-green: #C6FF00;
      --tf-pink: #E91E8C;
      --tf-text: #F5F5F5;
      --tf-text-muted: #D6D6D6;
      --tf-block-bg: #1a1a1a;
      --tf-block-border: #333;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: 'Satoshi', Arial, Helvetica, sans-serif;
      background: var(--tf-bg);
      color: var(--tf-text);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
    }
    .card {
      width: 100%;
      max-width: 400px;
      border: 4px solid #000;
      background: var(--tf-block-bg);
      box-shadow: 8px 8px #000;
      padding: 1.75rem 1.5rem;
    }
    h1 {
      font-family: 'Bangers', Impact, cursive;
      color: var(--tf-green);
      font-size: 1.75rem;
      margin: 0 0 1rem;
      letter-spacing: -0.02em;
    }
    label {
      display: block;
      font-size: 0.875rem;
      color: var(--tf-text-muted);
      margin-bottom: 0.35rem;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 0.65rem 0.75rem;
      margin-bottom: 1rem;
      border: 2px solid var(--tf-block-border);
      background: #0d0d0d;
      color: var(--tf-text);
      border-radius: 0;
      font-size: 1rem;
    }
    input:focus {
      outline: 2px solid var(--tf-green);
      outline-offset: 2px;
    }
    button {
      width: 100%;
      padding: 0.75rem 1rem;
      font-family: 'Bangers', Impact, cursive;
      font-size: 1.125rem;
      letter-spacing: 0.04em;
      background: var(--tf-green);
      color: #111;
      border: 3px solid #000;
      box-shadow: 4px 4px #000;
      cursor: pointer;
    }
    button:active { transform: translate(2px, 2px); box-shadow: 2px 2px #000; }
    .err {
      background: rgba(233, 30, 140, 0.15);
      border: 2px solid var(--tf-pink);
      color: var(--tf-text);
      padding: 0.65rem 0.75rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Panel pedidos</h1>
    <?php if ($error !== '') { ?>
      <div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>
    <form method="post" action="<?= htmlspecialchars($bp . '/' . $lang . '/admin', ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <label for="username">Usuario</label>
      <input id="username" name="username" type="text" autocomplete="username" required>
      <label for="password">Contraseña</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
