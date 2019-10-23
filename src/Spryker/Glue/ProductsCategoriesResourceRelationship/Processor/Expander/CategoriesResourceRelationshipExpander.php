<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Expander;

use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Dependency\RestResource\ProductsCategoriesResourceRelationToCategoriesRestApiResourceInterface;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Reader\AbstractProductsCategoriesReaderInterface;

class CategoriesResourceRelationshipExpander implements CategoriesResourceRelationshipExpanderInterface
{
    /**
     * @var \Spryker\Glue\ProductsCategoriesResourceRelationship\Dependency\RestResource\ProductsCategoriesResourceRelationToCategoriesRestApiResourceInterface
     */
    protected $categoriesRestApiResource;

    /**
     * @var \Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Reader\AbstractProductsCategoriesReaderInterface
     */
    protected $abstractProductsCategoriesReader;

    /**
     * @param \Spryker\Glue\ProductsCategoriesResourceRelationship\Dependency\RestResource\ProductsCategoriesResourceRelationToCategoriesRestApiResourceInterface $categoriesRestApiResource
     * @param \Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Reader\AbstractProductsCategoriesReaderInterface $abstractProductsCategoriesReader
     */
    public function __construct(
        ProductsCategoriesResourceRelationToCategoriesRestApiResourceInterface $categoriesRestApiResource,
        AbstractProductsCategoriesReaderInterface $abstractProductsCategoriesReader
    ) {
        $this->categoriesRestApiResource = $categoriesRestApiResource;
        $this->abstractProductsCategoriesReader = $abstractProductsCategoriesReader;
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface[] $resources
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return void
     */
    public function addResourceRelationships(array $resources, RestRequestInterface $restRequest): void
    {
        $locale = $restRequest->getMetadata()->getLocale();

        $skus = [];
        foreach ($resources as $resource) {
            $skus[] = $resource->getId();
        }

        $productCategoryNodeIds = $this->abstractProductsCategoriesReader
            ->findProductCategoryNodeIdsBySkus($skus, $locale);

        if ($productCategoryNodeIds === null) {
            return;
        }

        $categoryNodeIds = [];
        foreach ($productCategoryNodeIds as $productCategoryNodeId) {
            foreach ($productCategoryNodeId as $categoryId) {
                $categoryNodeIds[$categoryId] = $categoryId;
            }
        }

        $categoryNodes = $this->categoriesRestApiResource
            ->findCategoryNodeByIds($categoryNodeIds, $locale);

        foreach ($resources as $resource) {
            foreach ($productCategoryNodeIds[$resource->getId()] as $categoryNodeId) {
                $resource->addRelationship($categoryNodes[$categoryNodeId]);
            }
        }
    }
}
