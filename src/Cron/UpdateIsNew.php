<?php
declare(strict_types=1);

namespace IntegerNet\ProductIsNewAttribute\Cron;

use IntegerNet\ProductIsNewAttribute\Service\ProductsUpdateService;

class UpdateIsNew
{
    private ProductsUpdateService $productUpdateService;

    public function __construct(
        ProductsUpdateService $productUpdateService
    ) {
        $this->productUpdateService = $productUpdateService;
    }

    public function execute(): void
    {
        $this->productUpdateService->updateIsNewValues();
    }
}
