<?php

declare(strict_types=1);

use App\Lang\Lang;

/**
 * @var array<string, mixed> $order
 * @var string $flashOk
 * @var string $flashErr
 */

$lang = Lang::current();
$bp   = base_path();

$ref = (string) ($order['order_ref'] ?? '');
$ship = $order['shipping'] ?? [];
if (!is_array($ship)) {
    $ship = [];
}
$mode = (string) ($ship['shipping'] ?? '');
$tn   = isset($order['tracking_number']) && $order['tracking_number'] !== null ? trim((string) $order['tracking_number']) : '';
$labelUrl = isset($order['label_url']) && $order['label_url'] !== null ? trim((string) $order['label_url']) : '';
$pkErr    = isset($order['packlink_error']) && $order['packlink_error'] !== null ? trim((string) $order['packlink_error']) : '';
$st       = (string) ($order['status'] ?? '');

$addrLine = (string) ($ship['address_line'] ?? '');
$postal   = (string) ($ship['postal'] ?? '');
$city     = (string) ($ship['city'] ?? '');
$province = (string) ($ship['province'] ?? '');
$country  = (string) ($ship['country'] ?? '');
$phone    = (string) ($ship['phone'] ?? '');
$email    = (string) ($ship['email'] ?? $order['customer_email'] ?? '');
$name     = (string) ($order['customer_name'] ?? '');

$addrFull = trim($addrLine . ', ' . $postal . ' ' . $city . ', ' . $province . ', ' . $country, " ,");

$displayCents = (int) ($order['amount_cents'] ?? 0);
if (isset($ship['total_cents'])) {
    $displayCents = (int) $ship['total_cents'];
}

$showManualTracking = $mode === 'standard' && ($st === 'paid' || ($st === 'shipped' && $tn === ''));

// Seguimiento público Packlink Pro (doc. oficial ES): …/seguimiento/?reference=
$trackFollowUrl = $tn !== ''
    ? 'https://www.packlink.es/seguimiento/?reference=' . rawurlencode($tn)
    : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <title>Pedido #<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?> · Admin</title>
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
    body { margin: 0; font-family: 'Satoshi', Arial, sans-serif; background: var(--tf-bg); color: var(--tf-text); padding: 1rem 0.75rem 2.5rem; }
    .wrap { max-width: 720px; margin: 0 auto; }
    header { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 3px solid #000; }
    h1 { font-family: 'Bangers', Impact, cursive; color: var(--tf-green); font-size: clamp(1.35rem, 4vw, 1.75rem); margin: 0; flex: 1; }
    a.btn, button.btn {
      font-family: 'Bangers', Impact, cursive; font-size: 0.9rem; text-decoration: none; display: inline-block;
      padding: 0.45rem 0.85rem; border: 3px solid #000; box-shadow: 3px 3px #000; background: var(--tf-block-bg); color: var(--tf-green);
    }
    a.btn-primary { background: var(--tf-green); color: #111; }
    .card {
      border: 3px solid #000; background: var(--tf-block-bg); box-shadow: 6px 6px #000; padding: 1.25rem 1.1rem; margin-bottom: 1.25rem;
    }
    .card h2 { font-family: 'Bangers', Impact, cursive; color: var(--tf-green); font-size: 1.15rem; margin: 0 0 0.75rem; }
    dl { margin: 0; display: grid; grid-template-columns: 1fr; gap: 0.5rem; font-size: 0.9rem; }
    @media (min-width: 500px) {
      dl { grid-template-columns: 140px 1fr; }
    }
    dt { color: var(--tf-text-muted); }
    dd { margin: 0; word-break: break-word; }
    .flash-ok { background: rgba(50, 200, 80, 0.15); border: 2px solid #7dff9a; padding: 0.65rem 0.85rem; margin-bottom: 1rem; font-size: 0.9rem; }
    .flash-err { background: rgba(255, 77, 77, 0.12); border: 2px solid var(--tf-bad); padding: 0.65rem 0.85rem; margin-bottom: 1rem; font-size: 0.9rem; }
    .err-box { background: rgba(255, 77, 77, 0.1); border: 2px solid var(--tf-bad); padding: 0.75rem; margin-top: 0.75rem; font-size: 0.85rem; }
    .track-box { background: rgba(100, 220, 255, 0.08); border: 2px solid #9aebff; padding: 0.85rem; margin-top: 0.5rem; }
    .track-box a { color: #9aebff; font-weight: 700; }
    label { display: block; font-size: 0.8rem; color: var(--tf-text-muted); margin-bottom: 0.25rem; margin-top: 0.65rem; }
    input[type="text"], input[type="url"] {
      width: 100%; max-width: 100%; padding: 0.55rem 0.65rem; border: 2px solid var(--tf-block-border); background: #0d0d0d; color: var(--tf-text); font-size: 0.95rem;
    }
    button.submit {
      margin-top: 1rem; font-family: 'Bangers', Impact, cursive; font-size: 1rem; padding: 0.55rem 1rem;
      background: var(--tf-green); color: #111; border: 3px solid #000; cursor: pointer; box-shadow: 4px 4px #000;
    }
    .badge { display: inline-block; padding: 0.2rem 0.5rem; font-size: 0.8rem; font-weight: 700; border: 2px solid #000; }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>Pedido #<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></h1>
      <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
        <a class="btn" href="<?= htmlspecialchars($bp . '/' . $lang . '/admin/orders', ENT_QUOTES, 'UTF-8') ?>">← Lista</a>
        <a class="btn btn-primary" href="<?= htmlspecialchars($bp . '/' . $lang . '/admin/logout', ENT_QUOTES, 'UTF-8') ?>">Salir</a>
      </div>
    </header>

    <?php if ($flashOk !== '') { ?>
      <div class="flash-ok"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>
    <?php if ($flashErr !== '') { ?>
      <div class="flash-err"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <div class="card">
      <h2>Estado</h2>
      <p style="margin:0"><span class="badge" style="background:#333;color:#eee"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span></p>
      <?php if ($pkErr !== '') { ?>
        <div class="err-box"><strong>Packlink:</strong> <?= htmlspecialchars($pkErr, ENT_QUOTES, 'UTF-8') ?></div>
      <?php } ?>
      <?php if ($st === 'pending_transfer') { ?>
        <form style="margin-top:1rem" method="post" action="<?= htmlspecialchars($bp . '/' . $lang . '/admin/orders/' . rawurlencode($ref) . '/confirm', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <button class="submit" type="submit">Confirmar pago y crear envío</button>
        </form>
      <?php } ?>
    </div>

    <div class="card">
      <h2>Cliente y envío</h2>
      <dl>
        <dt>Nombre</dt><dd><?= htmlspecialchars($name !== '' ? $name : '—', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Email</dt><dd><?= htmlspecialchars($email !== '' ? $email : '—', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Teléfono</dt><dd><?= htmlspecialchars($phone !== '' ? $phone : '—', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Método</dt><dd><?= htmlspecialchars($mode === 'pickup' ? 'Recogida' : 'Envío estándar', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Dirección</dt><dd><?= htmlspecialchars($addrFull !== '' ? $addrFull : '—', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Importe</dt><dd><?= htmlspecialchars(format_price_cents($displayCents), ENT_QUOTES, 'UTF-8') ?></dd>
      </dl>
    </div>

    <?php if ($mode === 'standard' && ($tn !== '' || $labelUrl !== '')) { ?>
      <div class="card">
        <h2>Envío Packlink</h2>
        <?php if ($tn !== '') { ?>
          <div class="track-box">
            <div style="font-size:0.85rem;color:var(--tf-text-muted)">Tracking</div>
            <div style="font-size:1.15rem;font-weight:800;margin:0.35rem 0"><?= htmlspecialchars($tn, ENT_QUOTES, 'UTF-8') ?></div>
            <a href="<?= htmlspecialchars($trackFollowUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Seguimiento en Packlink →</a>
          </div>
        <?php } ?>
        <?php if ($labelUrl !== '' && filter_var($labelUrl, FILTER_VALIDATE_URL)) { ?>
          <p style="margin:0.75rem 0 0">
            <a class="btn btn-primary" href="<?= htmlspecialchars($labelUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Descargar etiqueta (PDF)</a>
          </p>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($showManualTracking) { ?>
      <div class="card">
        <h2>Tracking manual</h2>
        <p style="margin:0 0 0.5rem;font-size:0.88rem;color:var(--tf-text-muted)">Si Packlink no creó el envío automáticamente, introduce el número de seguimiento (el pedido sigue como pagado).</p>
        <form method="post" action="<?= htmlspecialchars($bp . '/' . $lang . '/admin/orders/' . rawurlencode($ref) . '/tracking', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <label for="tracking_number">Número de seguimiento</label>
          <input id="tracking_number" name="tracking_number" type="text" required maxlength="100" autocomplete="off">
          <label for="label_url">URL etiqueta PDF (opcional)</label>
          <input id="label_url" name="label_url" type="url" maxlength="500" placeholder="https://...">
          <button class="submit" type="submit">Guardar tracking</button>
        </form>
      </div>
    <?php } ?>
  </div>
</body>
</html>
