<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Lang\Lang;
use App\Models\Order;
use App\Security\AdminLoginRateLimiter;
use App\Services\OrderPostPaidActions;

final class AdminController
{
    private const SESSION_KEY = 'admin_authenticated';

    /** Inactividad máxima (segundos) antes de exigir login de nuevo. */
    private const IDLE_SECONDS = 7200;

    private function adminBasePath(): string
    {
        return base_path() . '/' . Lang::current() . '/admin';
    }

    private function sendAdminSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    /**
     * Elimina todo el estado del panel admin en la sesión PHP sin destruir
     * el resto (p. ej. carrito checkout en la misma cookie de sesión).
     */
    private function purgeAdminSessionState(): void
    {
        foreach (array_keys($_SESSION) as $k) {
            if (str_starts_with((string) $k, 'admin_')) {
                unset($_SESSION[$k]);
            }
        }
        unset($_SESSION[self::SESSION_KEY], $_SESSION['csrf_admin']);
    }

    public function dispatch(string $path, string $method): void
    {
        $this->sendAdminSecurityHeaders();

        $path = '/' . trim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        if ($path === '/admin/logout' && strtoupper($method) === 'GET') {
            $this->logout();

            return;
        }

        if ($path === '/admin' && strtoupper($method) === 'GET') {
            $this->loginGet();

            return;
        }

        if ($path === '/admin' && strtoupper($method) === 'POST') {
            $this->loginPost();

            return;
        }

        if ($path === '/admin/orders' && strtoupper($method) === 'GET') {
            $this->requireAuth();
            $this->ordersGet();

            return;
        }

        if (preg_match('#^/admin/orders/([^/]+)/confirm$#', $path, $m) === 1 && strtoupper($method) === 'POST') {
            $this->requireAuth();
            $this->confirmPost($m[1]);

            return;
        }

        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/404.php';
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            header('Location: ' . $this->adminBasePath(), true, 302);
            exit;
        }

        $last = (int) ($_SESSION['admin_last_activity'] ?? 0);
        if ($last <= 0 || (time() - $last) > self::IDLE_SECONDS) {
            $this->purgeAdminSessionState();
            if (!headers_sent()) {
                session_regenerate_id(true);
            }
            $_SESSION['admin_login_error'] = 'Sesión caducada por inactividad. Vuelve a iniciar sesión.';
            header('Location: ' . $this->adminBasePath(), true, 302);
            exit;
        }

        $_SESSION['admin_last_activity'] = time();
    }

    private function loginGet(): void
    {
        if (!empty($_SESSION[self::SESSION_KEY])) {
            header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
            exit;
        }

        $error = isset($_SESSION['admin_login_error']) ? (string) $_SESSION['admin_login_error'] : '';
        unset($_SESSION['admin_login_error']);

        require dirname(__DIR__, 2) . '/templates/admin/login.php';
    }

    private function loginPost(): void
    {
        if (!admin_verify_csrf((string) ($_POST['csrf'] ?? ''))) {
            $_SESSION['admin_login_error'] = 'Sesión de formulario caducada. Inténtalo de nuevo.';
            header('Location: ' . $this->adminBasePath(), true, 302);
            exit;
        }

        if (AdminLoginRateLimiter::isLocked()) {
            $_SESSION['admin_login_error'] = 'Demasiados intentos fallidos. Espera unos minutos antes de volver a intentarlo.';
            header('Location: ' . $this->adminBasePath(), true, 302);
            exit;
        }

        $cfg      = admin_config();
        $user     = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $hash     = trim($cfg['password_hash']);

        $validUser  = ($user === $cfg['username'] && $hash !== '');
        $verifyHash = $validUser ? $hash : AdminLoginRateLimiter::dummyPasswordHash();
        $passwordOk = password_verify($password, $verifyHash);

        if (!$validUser || !$passwordOk) {
            AdminLoginRateLimiter::recordFailure();
            $_SESSION['admin_login_error'] = 'Usuario o contraseña incorrectos.';
            header('Location: ' . $this->adminBasePath(), true, 302);
            exit;
        }

        AdminLoginRateLimiter::clear();

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY]       = true;
        $_SESSION['admin_last_activity']   = time();

        header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
        exit;
    }

    private function logout(): void
    {
        $this->purgeAdminSessionState();
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        header('Location: ' . $this->adminBasePath(), true, 302);
        exit;
    }

    private function ordersGet(): void
    {
        $filter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : 'all';
        $orders = Order::listForAdmin($filter);
        $flashOk = isset($_SESSION['admin_flash_ok']) ? (string) $_SESSION['admin_flash_ok'] : '';
        unset($_SESSION['admin_flash_ok']);
        $flashErr = isset($_SESSION['admin_flash_err']) ? (string) $_SESSION['admin_flash_err'] : '';
        unset($_SESSION['admin_flash_err']);

        require dirname(__DIR__, 2) . '/templates/admin/orders.php';
    }

    private function confirmPost(string $orderRef): void
    {
        if (!admin_verify_csrf((string) ($_POST['csrf'] ?? ''))) {
            $_SESSION['admin_flash_err'] = 'No se pudo confirmar el pago (token de seguridad inválido).';
            header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
            exit;
        }

        $orderRef = trim($orderRef);
        if ($orderRef === '') {
            $_SESSION['admin_flash_err'] = 'Referencia de pedido no válida.';
            header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
            exit;
        }

        $before = Order::getForEmail($orderRef);
        if ($before === null) {
            $_SESSION['admin_flash_err'] = 'Pedido no encontrado.';
            header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
            exit;
        }

        if (($before['status'] ?? '') !== 'pending_transfer') {
            $_SESSION['admin_flash_err'] = 'Solo se pueden confirmar pedidos en espera de transferencia.';
            header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
            exit;
        }

        $payload = [
            'source'       => 'admin_transfer_confirm',
            'confirmed_at' => gmdate('c'),
        ];

        try {
            $ok = Order::markAsPaid($orderRef, $payload);
        } catch (\Throwable $e) {
            error_log('Admin confirm markAsPaid: ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            $_SESSION['admin_flash_err'] = 'No se pudo marcar el pedido como pagado (¿ya estaba pagado?).';
            header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
            exit;
        }

        $after = Order::getForEmail($orderRef);
        if ($after !== null) {
            OrderPostPaidActions::afterManualTransferPaid($after);
        }

        $_SESSION['admin_flash_ok'] = 'Pago confirmado para el pedido #' . $orderRef . '. Se ha notificado al cliente.';
        header('Location: ' . $this->adminBasePath() . '/orders', true, 302);
        exit;
    }
}
