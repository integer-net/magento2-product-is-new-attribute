<?php
declare(strict_types=1);

namespace IntegerNet\ProductIsNewAttribute\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductsUpdateService
{
    public const ATTRIBUTE_CODE_NEW_FROM_DATE = 'news_from_date';
    public const ATTRIBUTE_CODE_NEW_TO_DATE   = 'news_to_date';
    public const ATTRIBUTE_CODE_IS_NEW        = 'is_new';

    private ProductCollectionFactory $productCollectionFactory;
    private StoreManagerInterface    $storeManager;
    private TimezoneInterface        $timezone;
    private ProductResource          $productResource;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone,
        ProductResource $productResource
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
        $this->productResource = $productResource;
    }

    public function updateIsNewValues(): void
    {
        foreach ($this->storeManager->getStores(true) as $store) {
            foreach ($this->getProductsToCheck($store) as $product) {
                $hasProductNewAttributeValue = $product->hasData(self::ATTRIBUTE_CODE_IS_NEW);
                $isProductNew = $this->isProductNew($product);
                $wasProductNew = (bool)$product->getData(self::ATTRIBUTE_CODE_IS_NEW);
                if (!$hasProductNewAttributeValue || ($isProductNew != $wasProductNew)) {
                    $product->setData(self::ATTRIBUTE_CODE_IS_NEW, $isProductNew);
                    $this->productResource->saveAttribute($product, self::ATTRIBUTE_CODE_IS_NEW);
                }
            }
        }
    }

    /**
     * @param StoreInterface $store
     * @return array|ProductInterface[]
     */
    private function getProductsToCheck(StoreInterface $store): array
    {
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStore($store);
        $productCollection->addAttributeToSelect([
            self::ATTRIBUTE_CODE_IS_NEW,
            self::ATTRIBUTE_CODE_NEW_FROM_DATE,
            self::ATTRIBUTE_CODE_NEW_TO_DATE,
        ]);
//        $productCollection->addAttributeToFilter( // OR filter
//            [
//                [
//                    'attribute' => self::ATTRIBUTE_CODE_NEW_FROM_DATE,
//                    'notnull'   => true,
//                ],
//                [
//                    'attribute' => self::ATTRIBUTE_CODE_NEW_TO_DATE,
//                    'notnull'   => true,
//                ],
//            ]
//        );
        return $productCollection->getItems();
    }

    private function isProductNew(ProductInterface $product): bool
    {
        $newsFromDate = (string)$product->getData(self::ATTRIBUTE_CODE_NEW_FROM_DATE);
        $newsToDate = (string)$product->getData(self::ATTRIBUTE_CODE_NEW_TO_DATE);
        if (!$newsFromDate && !$newsToDate) {
            return false;
        }

        return $this->timezone->isScopeDateInInterval(
            $product->getStore(),
            $newsFromDate,
            $newsToDate
        );
    }
}
