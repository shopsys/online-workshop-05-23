<?php

namespace SS6\ShopBundle\Model\Product;

use Doctrine\ORM\EntityManager;
use SS6\ShopBundle\Model\Domain\Domain;
use SS6\ShopBundle\Model\Image\ImageFacade;
use SS6\ShopBundle\Model\Pricing\Group\PricingGroupRepository;
use SS6\ShopBundle\Model\Pricing\Vat\Vat;
use SS6\ShopBundle\Model\Product\Parameter\ParameterRepository;
use SS6\ShopBundle\Model\Product\Parameter\ProductParameterValue;
use SS6\ShopBundle\Model\Product\Pricing\ProductManualInputPriceFacade;
use SS6\ShopBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use SS6\ShopBundle\Model\Product\Product;
use SS6\ShopBundle\Model\Product\ProductEditData;
use SS6\ShopBundle\Model\Product\ProductRepository;
use SS6\ShopBundle\Model\Product\ProductService;
use SS6\ShopBundle\Model\Product\ProductVisibilityFacade;

class ProductEditFacade {

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductRepository
	 */
	private $productRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductVisibilityFacade
	 */
	private $productVisibilityFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Parameter\ParameterRepository
	 */
	private $parameterRepository;

	/**
	 * @var SS6\ShopBundle\Model\Domain\Domain
	 */
	private $domain;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductService
	 */
	private $productService;

	/**
	 * @var \SS6\ShopBundle\Model\Image\ImageFacade
	 */
	private $imageFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler
	 */
	private $productPriceRecalculationScheduler;

	/**
	 * @var \SS6\ShopBundle\Model\Pricing\Group\PricingGroupRepository
	 */
	private $pricingGroupRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Pricing\ProductManualInputPriceFacade
	 */
	private $productManualInputPriceFacade;

	public function __construct(
		EntityManager $em,
		ProductRepository $productRepository,
		ProductVisibilityFacade $productVisibilityFacade,
		ParameterRepository $parameterRepository,
		Domain $domain,
		ProductService $productService,
		ImageFacade	$imageFacade,
		ProductPriceRecalculationScheduler $productPriceRecalculationScheduler,
		PricingGroupRepository $pricingGroupRepository,
		ProductManualInputPriceFacade $productManualInputPriceFacade
	) {
		$this->em = $em;
		$this->productRepository = $productRepository;
		$this->productVisibilityFacade = $productVisibilityFacade;
		$this->parameterRepository = $parameterRepository;
		$this->domain = $domain;
		$this->productService = $productService;
		$this->imageFacade = $imageFacade;
		$this->productPriceRecalculationScheduler = $productPriceRecalculationScheduler;
		$this->pricingGroupRepository = $pricingGroupRepository;
		$this->productManualInputPriceFacade = $productManualInputPriceFacade;
	}

	/**
	 * @param int $productId
	 * @return \SS6\ShopBundle\Model\Product\Product
	 */
	public function getById($productId) {
		return $this->productRepository->getById($productId);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\ProductEditData $productEditData
	 * @return \SS6\ShopBundle\Model\Product\Product
	 */
	public function create(ProductEditData $productEditData) {
		$product = new Product($productEditData->productData);

		$this->em->persist($product);
		$this->em->beginTransaction();
		$this->saveParameters($product, $productEditData->parameters);
		$this->createProductDomains($product, $this->domain->getAll());
		$this->refreshProductDomains($product, $productEditData->productData->hiddenOnDomains);
		$this->refreshProductManualInputPrices($product, $productEditData->manualInputPrices);
		$this->em->flush();
		$this->imageFacade->uploadImages($product, $productEditData->imagesToUpload, null);
		$this->em->commit();

		$this->productVisibilityFacade->refreshProductsVisibilityDelayed();
		$this->productPriceRecalculationScheduler->scheduleRecalculatePriceForProduct($product);

		return $product;
	}

	/**
	 * @param int $productId
	 * @param \SS6\ShopBundle\Model\Product\ProductEditData $productEditData
	 * @return \SS6\ShopBundle\Model\Product\Product
	 */
	public function edit($productId, ProductEditData $productEditData) {
		$product = $this->productRepository->getById($productId);

		$this->productService->edit($product, $productEditData->productData);

		$this->em->beginTransaction();
		$this->saveParameters($product, $productEditData->parameters);
		$this->refreshProductDomains($product, $productEditData->productData->hiddenOnDomains);
		$this->refreshProductManualInputPrices($product, $productEditData->manualInputPrices);
		$this->em->flush();
		$this->imageFacade->uploadImages($product, $productEditData->imagesToUpload, null);
		$this->imageFacade->deleteImages($product, $productEditData->imagesToDelete);
		$this->em->commit();

		$this->productVisibilityFacade->refreshProductsVisibilityDelayed();

		return $product;
	}

	/**
	 * @param int $productId
	 */
	public function delete($productId) {
		$product = $this->productRepository->getById($productId);
		$this->em->remove($product);
		$this->em->flush();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @param \SS6\ShopBundle\Model\Product\Parameter\ProductParameterValueData[] $productParameterValuesData
	 */
	private function saveParameters(Product $product, array $productParameterValuesData) {
		// Doctrine runs INSERTs before DELETEs in UnitOfWork. In case of UNIQUE constraint
		// in database, this leads in trying to insert duplicate entry.
		// That's why it's necessary to do remove and flush first.

		$oldProductParameterValues = $this->parameterRepository->getProductParameterValuesByProduct($product);
		foreach ($oldProductParameterValues as $oldProductParameterValue) {
			$this->em->remove($oldProductParameterValue);
		}
		$this->em->flush();

		foreach ($productParameterValuesData as $productParameterValueData) {
			$productParameterValueData->product = $product;
			$productParameterValue = new ProductParameterValue(
				$productParameterValueData->product,
				$productParameterValueData->parameter,
				$productParameterValueData->locale,
				$this->parameterRepository->findOrCreateParameterValueByValueText($productParameterValueData->valueText)
			);
			$this->em->persist($productParameterValue);
		}
		$this->em->flush();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Pricing\Vat\Vat $oldVat
	 * @param \SS6\ShopBundle\Model\Pricing\Vat\Vat $newVat
	 */
	public function replaceOldVatWithNewVat(Vat $oldVat, Vat $newVat) {
		$products = $this->productRepository->getAllByVat($oldVat);
		foreach ($products as $product) {
			$this->productService->replaceOldVatWithNewVat($product, $newVat);
		}
		$this->em->flush();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Pricing\Vat\Vat $oldVat
	 * @param string $newVatPercent
	 */
	public function recalculateInputPricesForNewVatPercent(Vat $oldVat, $newVatPercent) {
		$products = $this->productRepository->getAllByVat($oldVat);
		foreach ($products as $product) {
			$this->productService->recalculateInputPriceForNewVatPercent($product, $newVatPercent);
		}
		$this->em->flush();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @param \SS6\ShopBundle\Model\Domain\Config\DomainConfig[] $domains
	 */
	private function createProductDomains(Product $product, array $domains) {
		foreach ($domains as $domain) {
			$productDomain = new ProductDomain($product, $domain->getId());
			$this->em->persist($productDomain);
		}
		$this->em->flush();

	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @param array $hiddenOnDomainData
	 */
	private function refreshProductDomains(Product $product, array $hiddenOnDomainData) {
		$productDomains = $this->productRepository->getProductDomainsByProduct($product);
		foreach ($productDomains as $productDomain) {
			if (in_array($productDomain->getDomainId(), $hiddenOnDomainData)) {
				$productDomain->setHidden(true);
			} else {
				$productDomain->setHidden(false);
			}
		}
		$this->em->flush();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @return \SS6\ShopBundle\Model\Product\Pricing\ProductSellingPrice[]
	 */
	public function getAllProductSellingPricesIndexedByDomainId(Product $product) {
		return $this->productService->getProductSellingPricesIndexedByDomainIdAndPricingGroupId(
			$product,
			$this->pricingGroupRepository->getAll()
		);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @param string[] $manualInputPrices
	 */
	private function refreshProductManualInputPrices(Product $product, array $manualInputPrices) {
		if ($product->getPriceCalculationType() === Product::PRICE_CALCULATION_TYPE_AUTO) {
			$this->productManualInputPriceFacade->deleteByProduct($product);
		} else {
			foreach ($this->pricingGroupRepository->getAll() as $pricingGroup) {
				$this->productManualInputPriceFacade->refresh($product, $pricingGroup, $manualInputPrices[$pricingGroup->getId()]);
			}
		}
	}

}
