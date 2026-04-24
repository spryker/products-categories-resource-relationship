<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsCategoriesResourceRelationship\Api\Storefront\Relationship;

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
        $localeName = $this->hasLocale() ? $this->getLocale()->getLocaleNameOrFail() : '';
        $storeName = $this->hasStore() ? $this->getStore()->getNameOrFail() : '';

        $nodeIds = $this->collectCategoryNodeIds($localeName, $storeName);

        if ($nodeIds === []) {
            return [];
        }

        $categoryNodeStorageTransfers = $this->categoryStorageClient->getCategoryNodeByIds(
            $nodeIds,
            $localeName,
            $storeName,
        );

        $resources = [];
        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            if ($categoryNodeStorageTransfer->getIdCategory() === null) {
                continue;
            }

            $resources[] = $this->mapToResource($categoryNodeStorageTransfer);
        }

        return $resources;
    }

    /**
     * @return array<int>
     */
    protected function collectCategoryNodeIds(string $localeName, string $storeName): array
    {
        $nodeIds = [];

        foreach ($this->getParentResources() as $parent) {
            $sku = $parent->sku ?? null;

            if (!is_string($sku) || $sku === '') {
                continue;
            }

            $productAbstractData = $this->productStorageClient->findProductAbstractStorageDataByMapping(
                static::MAPPING_TYPE_SKU,
                $sku,
                $localeName,
            );

            if ($productAbstractData === null) {
                continue;
            }

            $idProductAbstract = (int)($productAbstractData[static::KEY_ID_PRODUCT_ABSTRACT] ?? 0);

            if ($idProductAbstract === 0) {
                continue;
            }

            $productAbstractCategoryStorage = $this->productCategoryStorageClient->findProductAbstractCategory(
                $idProductAbstract,
                $localeName,
                $storeName,
            );

            if ($productAbstractCategoryStorage === null) {
                continue;
            }

            foreach ($productAbstractCategoryStorage->getCategories() as $category) {
                $nodeId = $category->getCategoryNodeId();

                if ($nodeId === null) {
                    continue;
                }

                $nodeIds[(int)$nodeId] = (int)$nodeId;
            }
        }

        return array_values($nodeIds);
    }

    protected function mapToResource(CategoryNodeStorageTransfer $node): CategoryNodesStorefrontResource
    {
        $resource = new CategoryNodesStorefrontResource();
        $resource->nodeId = (string)$node->getNodeId();
        $resource->name = $node->getName();
        $resource->metaTitle = $node->getMetaTitle();
        $resource->metaKeywords = $node->getMetaKeywords();
        $resource->metaDescription = $node->getMetaDescription();
        $resource->isActive = $node->getIsActive();
        $resource->order = $node->getOrder();
        $resource->url = $node->getUrl();
        $resource->children = $this->toArray($node->getChildren());
        $resource->parents = $this->toArray($node->getParents());

        return $resource;
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\CategoryNodeStorageTransfer> $nodes
     *
     * @return array<int, array<string, mixed>>
     */
    protected function toArray(iterable $nodes): array
    {
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $node->toArray();
        }

        return $result;
    }
}
