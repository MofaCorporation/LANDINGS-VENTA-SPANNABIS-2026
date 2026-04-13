<?php

declare(strict_types=1);

use App\Lang\Lang;

/**
 * @var string $filter
 * @var list<array<string, mixed>> $orders
 * @var string $flashOk
 * @var string $flashErr
 */

$lang = Lang::current();
$bp = base_path();

$formatShip = static function (?string $json): string {
    if ($json === null || $json === '') {
        return '—';
    }
    try {
        $d = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable) {
        return '—';
    }
    if (!is_array($d)) {
        return '—';
    }
    $method = isset($d['shipping']) ? (string) $d['shipping'] : '';
    $cents  = isset($d['shipping_cents']) ? (int) $d['shipping_cents'] : null;
    if ($method === 'pickup') {
        $label = 'Recogida';
    } elseif ($method !== '') {
        $label = 'Envío';
    } else {
        $label = '—';
    }
    if ($cents !== null && $cents > 0) {
        $label .= ' · ' . format_price_cents($cents);
    } elseif ($method === '' && $cents === null) {
        return '—';
    }

    return $label;
};

$statusClass = static function (string $st): string {
    return match ($st) {
        'pending_transfer' => 'st-pending',
        'paid'             => 'st-paid',
        'shipped'          => 'st-shipped',
        'failed'           => 'st-failed',
        default            => 'st-other',
    };
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <title>Pedidos · Admin · Tarumba's Farm</title>
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
      --tf-warn: #f5d000;
      --tf-bad: #ff4d4d;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Satoshi', Arial, Helvetica, sans-serif;
      background: var(--tf-bg);
      color: var(--tf-text);
      min-height: 100vh;
      padding: 1rem 0.75rem 2rem;
    }
    .wrap { max-width: 1200px; margin: 0 auto; }
    header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.75rem 1rem;
      margin-bottom: 1.25rem;
      border-bottom: 3px solid #000;
      padding-bottom: 1rem;
    }
    h1 {
      font-family: 'Bangers', Impact, cursive;
      color: var(--tf-green);
      font-size: clamp(1.35rem, 4vw, 1.85rem);
      margin: 0;
      flex: 1 1 auto;
    }
    .top-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
    a.btn, button.btn {
      font-family: 'Bangers', Impact, cursive;
      font-size: 0.95rem;
      letter-spacing: 0.03em;
      text-decoration: none;
      display: inline-block;
      padding: 0.45rem 0.85rem;
      border: 3px solid #000;
      box-shadow: 3px 3px #000;
      background: var(--tf-block-bg);
      color: var(--tf-green);
      cursor: pointer;
    }
    a.btn-primary { background: var(--tf-green); color: #111; }
    .filters {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
      align-items: center;
    }
    .filters span { color: var(--tf-text-muted); font-size: 0.875rem; margin-right: 0.25rem; }
    .filters a {
      color: var(--tf-green);
      text-decoration: none;
      font-size: 0.9rem;
      padding: 0.25rem 0.5rem;
      border: 2px solid transparent;
    }
    .filters a.on { border-color: var(--tf-green); }
    .scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 3px solid #000; background: var(--tf-block-bg); box-shadow: 6px 6px #000; }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8125rem;
      min-width: 720px;
    }
    th, td {
      text-align: left;
      padding: 0.55rem 0.65rem;
      border-bottom: 1px solid var(--tf-block-border);
      vertical-align: top;
    }
    th {
      font-family: 'Bangers', Impact, cursive;
      color: var(--tf-green);
      font-size: 0.95rem;
      font-weight: normal;
      letter-spacing: 0.02em;
    }
    tr:hover td { background: rgba(198, 255, 0, 0.04); }
    .ref { font-weight: 700; color: var(--tf-text); }
    .muted { color: var(--tf-text-muted); font-size: 0.8rem; }
    .badge {
      display: inline-block;
      padding: 0.2rem 0.45rem;
      font-size: 0.75rem;
      font-weight: 700;
      border: 2px solid #000;
    }
    .st-pending { background: rgba(245, 208, 0, 0.25); color: var(--tf-warn); }
    .st-paid { background: rgba(50, 200, 80, 0.2); color: #7dff9a; }
    .st-shipped { background: rgba(100, 220, 255, 0.18); color: #9aebff; }
    .st-failed { background: rgba(255, 77, 77, 0.2); color: var(--tf-bad); }
    .st-other { background: #333; color: #ccc; }
    form.inline { margin: 0; display: inline; }
    button.confirm {
      font-family: 'Satoshi', Arial, sans-serif;
      font-weight: 700;
      font-size: 0.75rem;
      padding: 0.35rem 0.55rem;
      background: var(--tf-green);
      color: #111;
      border: 2px solid #000;
      cursor: pointer;
    }
    .flash-ok {
      background: rgba(50, 200, 80, 0.15);
      border: 2px solid #7dff9a;
      padding: 0.65rem 0.85rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
    .flash-err {
      background: rgba(255, 77, 77, 0.12);
      border: 2px solid var(--tf-bad);
      padding: 0.65rem 0.85rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>Pedidos</h1>
      <div class="top-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars($bp . '/' . $lang . '/pepebulkov/logout', ENT_QUOTES, 'UTF-8') ?>">Cerrar sesión</a>
      </div>
    </header>

    <?php if ($flashOk !== '') { ?>
      <div class="flash-ok"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>
    <?php if ($flashErr !== '') { ?>
      <div class="flash-err"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <div class="filters">
      <span>Estado:</span>
      <?php
        $opts = [
            'all'               => 'Todos',
            'pending_transfer'  => 'Pendiente transferencia',
            'paid'              => 'Pagado',
            'shipped'           => 'Enviado',
            'failed'            => 'Fallido',
        ];
        foreach ($opts as $val => $label) {
            $on = ($filter === $val) ? ' on' : '';
            $href = $bp . '/' . $lang . '/pepebulkov/orders' . ($val === 'all' ? '' : ('?status=' . rawurlencode($val)));
            echo '<a class="' . trim($on) . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
        }
      ?>
    </div>

    <div class="scroll">
      <table>
        <thead>
          <tr>
            <th># ref</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Email</th>
            <th>Variedad</th>
            <th>Importe</th>
            <th>Envío</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $row) {
            $ref = (string) ($row['order_ref'] ?? '');
            $created = (string) ($row['created_at'] ?? '');
            $st = (string) ($row['status'] ?? '');
            $name = (string) ($row['customer_name'] ?? '');
            $email = (string) ($row['customer_email'] ?? '');
            $nameEs = (string) ($row['name_es'] ?? '');
            $slug = (string) ($row['slug_es'] ?? '');
            $amt = (int) ($row['amount_cents'] ?? 0);
            $shipJson = isset($row['shipping_json']) && is_string($row['shipping_json']) ? $row['shipping_json'] : null;
            $displayCents = $amt;
            if ($shipJson !== null) {
                try {
                    $sj = json_decode($shipJson, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($sj) && isset($sj['total_cents'])) {
                        $displayCents = (int) $sj['total_cents'];
                    }
                } catch (\Throwable) {
                }
            }
            ?>
          <tr>
            <td class="ref"><a href="<?= htmlspecialchars($bp . '/' . $lang . '/pepebulkov/orders/' . rawurlencode($ref), ENT_QUOTES, 'UTF-8') ?>" style="color:var(--tf-green);font-weight:700">#<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></a></td>
            <td><?= htmlspecialchars($created, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($name !== '' ? $name : '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($email !== '' ? $email : '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?= htmlspecialchars($nameEs !== '' ? $nameEs : '—', ENT_QUOTES, 'UTF-8') ?>
              <?php if ($slug !== '') { ?>
                <div class="muted"><?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></div>
              <?php } ?>
            </td>
            <td><?= htmlspecialchars(format_price_cents($displayCents), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($formatShip($shipJson), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <span class="badge <?= htmlspecialchars($statusClass($st), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td>
              <?php if ($st === 'pending_transfer') { ?>
                <form class="inline" method="post" action="<?= htmlspecialchars($bp . '/' . $lang . '/pepebulkov/orders/' . rawurlencode($ref) . '/confirm', ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                  <button class="confirm" type="submit">Confirmar pago</button>
                </form>
              <?php } else { ?>
                <span class="muted">—</span>
              <?php } ?>
            </td>
          </tr>
        <?php } ?>
        <?php if ($orders === []) { ?>
          <tr><td colspan="9" class="muted" style="text-align:center;padding:2rem">No hay pedidos.</td></tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
