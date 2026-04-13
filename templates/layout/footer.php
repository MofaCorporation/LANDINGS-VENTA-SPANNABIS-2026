<?php

declare(strict_types=1);

use App\Lang\Lang;

$checkoutUi = !empty($checkoutUi);
?>
<?php if (!$checkoutUi) : ?>
<?php if (!empty($useSiteFooterBlock)) : ?>
<?php require __DIR__ . '/../partials/site_legal_and_footer.php'; ?>
<?php endif; ?>
</div>
<?php endif; ?>
</body>
</html>
