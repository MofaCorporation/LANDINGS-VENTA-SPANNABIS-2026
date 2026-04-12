<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Services\OrderPostPaidActions;
use App\Services\RedsysService;

final class RedsysController extends BaseController
{
    public function notify(): void
    {
        $version  = (string) ($_POST['Ds_SignatureVersion'] ?? '');
        $params64 = (string) ($_POST['Ds_MerchantParameters'] ?? '');
        $sig      = (string) ($_POST['Ds_Signature'] ?? '');

        try {
            $redsys = new RedsysService(RedsysService::loadConfig());
            $data   = $redsys->validateNotification($version, $params64, $sig);
        } catch (\Throwable $e) {
            error_log('Redsys notify: ' . $e->getMessage());
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'INVALID';

            return;
        }

        $orderRef = self::notifParam($data, 'Ds_Order');
        $respRaw  = self::notifParam($data, 'Ds_Response');
        $code     = (int) $respRaw;

        if ($orderRef === '') {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'NO_ORDER';

            return;
        }

        if ($code < 100) {
            try {
                $transitioned = Order::markAsPaid($orderRef, $data);
                if ($transitioned) {
                    $this->onPaid($orderRef);
                }
            } catch (\Throwable $e) {
                error_log('Order::markAsPaid: ' . $e->getMessage());
            }
            http_response_code(200);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'OK';

            return;
        }

        try {
            Order::markAsFailed($orderRef, $code);
        } catch (\Throwable $e) {
            error_log('Order::markAsFailed: ' . $e->getMessage());
        }

        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'KO';
    }

    public function ok(): void
    {
        $variety = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety'])
            ? $_SESSION['checkout_variety']
            : null;

        $this->render('checkout_ok', [
            'pageTitleKey'       => 'checkout.ok_page_title',
            'metaDescriptionKey' => 'checkout.meta_description',
            'checkoutUi'         => true,
            'extraModuleSrc'     => null,
            'checkoutVariety'    => $variety,
        ]);
    }

    public function ko(): void
    {
        $variety = isset($_SESSION['checkout_variety']) && is_string($_SESSION['checkout_variety'])
            ? $_SESSION['checkout_variety']
            : null;

        $this->render('checkout_ko', [
            'pageTitleKey'       => 'checkout.ko_page_title',
            'metaDescriptionKey' => 'checkout.meta_description',
            'checkoutUi'         => true,
            'extraModuleSrc'     => null,
            'checkoutVariety'    => $variety,
        ]);
    }

    /** @param array<string, mixed> $params */
    private static function notifParam(array $params, string $name): string
    {
        $want = strtolower($name);
        foreach ($params as $k => $v) {
            if (strtolower((string) $k) === $want) {
                return (string) $v;
            }
        }

        return '';
    }

    private function onPaid(string $orderRef): void
    {
        $order = Order::getForEmail($orderRef);
        if ($order === null) {
            return;
        }

        OrderPostPaidActions::afterGatewayPaid($order);
    }
}
