<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductsCategoriesResourceRelationship;

use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Dependency\Client\ProductsCategoriesResourceRelationshipToProductCategoryStorageClientInterface;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Dependency\Client\ProductsCategoriesResourceRelationshipToProductStorageClientInterface;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Dependency\Client\ProductsCategoriesResourceRelationshipToStoreClientInterface;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Dependency\RestResource\ProductsCategoriesResourceRelationToCategoriesRestApiResourceInterface;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Expander\CategoriesResourceRelationshipExpander;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Expander\CategoriesResourceRelationshipExpanderInterface;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Reader\AbstractProductsCategoriesReader;
use Spryker\Glue\ProductsCategoriesResourceRelationship\Processor\Reader\AbstractProductsCategoriesReaderInterface;

class ProductsCategoriesResourceRelationshipFactory extends AbstractFactory
{
    public function getCategoriesResource(): ProductsCategoriesResourceRelationToCategoriesRestApiResourceInterface
    {
        return $this->getProvidedDependency(ProductsCategoriesResourceRelationshipDependencyProvider::RESOURCE_CATEGORY);
    }

    public function createAbstractProductsCategoriesResourceRelationshipExpander(): CategoriesResourceRelationshipExpanderInterface
    {
        return new CategoriesResourceRelationshipExpander(
            $this->getCategoriesResource(),
            $this->createAbstractProductsCategoriesReader(),
        );
    }

    public function getProductStorageClient(): ProductsCategoriesResourceRelationshipToProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductsCategoriesResourceRelationshipDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    public function getProductCategoryStorageClient(): ProductsCategoriesResourceRelationshipToProductCategoryStorageClientInterface
    {
        return $this->getProvidedDependency(ProductsCategoriesResourceRelationshipDependencyProvider::CLIENT_PRODUCT_CATEGORY_STORAGE);
    }

    public function createAbstractProductsCategoriesReader(): AbstractProductsCategoriesReaderInterface
    {
        return new AbstractProductsCategoriesReader(
            $this->getProductStorageClient(),
            $this->getProductCategoryStorageClient(),
            $this->getStoreClient(),
        );
    }

    public function getStoreClient(): ProductsCategoriesResourceRelationshipToStoreClientInterface
    {
        return $this->getProvidedDependency(ProductsCategoriesResourceRelationshipDependencyProvider::CLIENT_STORE);
    }
}
