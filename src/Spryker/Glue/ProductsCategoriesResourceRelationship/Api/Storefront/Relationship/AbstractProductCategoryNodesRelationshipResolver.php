<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsCategoriesResourceRelationship\Api\Storefront\Relationship;

use ArrayObject;
use Generated\Api\Storefront\CategoryNodesStorefrontResource;
use Generated\Shared\Transfer\CategoryNodeStorageTransfer;
use Spryker\ApiPlatform\Relationship\AbstractRelationshipResolver;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Spryker\Client\ProductCategoryStorage\ProductCategoryStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class AbstractProductCategoryNodesRelationshipResolver extends AbstractRelationshipResolver
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductCategoryStorageClientInterface $productCategoryStorageClient,
        protected CategoryStorageClientInterface $categoryStorageClient,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\CategoryNodesStorefrontResource>
     */
    protected function resolveRelationship(): array
    {
        $locale = $this->getLocale()->getLocaleName() ?? '';
        $nodeIds = $this->extractCategoryNodeIds($this->getParentResources(), $locale);

        if ($nodeIds === []) {
            return [];
        }

        $storeName = $this->getStore()->getNameOrFail();
        $categoryNodeStorageTransfers = $this->categoryStorageClient->getCategoryNodeByIds($nodeIds, $locale, $storeName);

        $resources = [];

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            if (!$categoryNodeStorageTransfer->getIdCategory()) {
                continue;
            }

            $resources[] = CategoryNodesStorefrontResource::fromArray(
                $this->prepareNodeResourceData($categoryNodeStorageTransfer),
            );
        }

        return $resources;
    }

    /**
     * @param array<object> $parentResources
     *
     * @return array<int>
     */
    protected function extractCategoryNodeIds(array $parentResources, string $locale): array
    {
        $storeName = $this->getStore()->getName();

        if ($storeName === null) {
            return [];
        }

        $productAbstractIds = $this->resolveProductAbstractIds($parentResources, $locale);

        if ($productAbstractIds === []) {
            return [];
        }

        $productAbstractCategoryTransfers = $this->productCategoryStorageClient->findBulkProductAbstractCategory(
            $productAbstractIds,
            $locale,
            $storeName,
        );

        $nodeIds = [];

        foreach ($productAbstractCategoryTransfers as $productAbstractCategoryTransfer) {
            foreach ($productAbstractCategoryTransfer->getCategories() as $categoryTransfer) {
                $nodeId = $categoryTransfer->getCategoryNodeId();

                if ($nodeId !== null) {
                    $nodeIds[$nodeId] = $nodeId;
                }
            }
        }

        return array_values($nodeIds);
    }

    /**
     * @param array<object> $parentResources
     *
     * @return array<int>
     */
    protected function resolveProductAbstractIds(array $parentResources, string $locale): array
    {
        $skus = [];

        foreach ($parentResources as $abstractProductResource) {
            $sku = $abstractProductResource->sku ?? null;

            if (is_string($sku) && $sku !== '') {
                $skus[] = $sku;
            }
        }

        if ($skus === []) {
            return [];
        }

        $bulkStorageData = $this->productStorageClient->findBulkProductAbstractStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $skus,
            $locale,
        );

        $productAbstractIds = [];

        foreach ($bulkStorageData as $storageData) {
            $idProductAbstract = $storageData[static::KEY_ID_PRODUCT_ABSTRACT] ?? null;

            if ($idProductAbstract !== null) {
                $productAbstractIds[] = (int)$idProductAbstract;
            }
        }

        return $productAbstractIds;
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareNodeResourceData(CategoryNodeStorageTransfer $categoryNodeStorageTransfer): array
    {
        $data = $categoryNodeStorageTransfer->toArray(false, true);
        $data['categoryNodeId'] = (string)$categoryNodeStorageTransfer->getNodeId();
        $data['children'] = $this->mapNodeCollection($categoryNodeStorageTransfer->getChildren());
        $data['parents'] = $this->mapNodeCollection($categoryNodeStorageTransfer->getParents());

        return $data;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\CategoryNodeStorageTransfer> $nodes
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapNodeCollection(ArrayObject $nodes): array
    {
        $result = [];

        foreach ($nodes as $nodeTransfer) {
            $result[] = $nodeTransfer->toArray(true, true);
        }

        return $result;
    }
}
