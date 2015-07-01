<?php

namespace SS6\ShopBundle\Model\Product\Detail;

use SS6\ShopBundle\Model\Image\ImageFacade;
use SS6\ShopBundle\Model\Localization\Localization;
use SS6\ShopBundle\Model\Product\Parameter\ParameterRepository;
use SS6\ShopBundle\Model\Product\Pricing\ProductPriceCalculation;
use SS6\ShopBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use SS6\ShopBundle\Model\Product\Product;
use SS6\ShopBundle\Model\Product\ProductRepository;

class ProductDetailFactory {

	/**
	 * @var \SS6\ShopBundle\Model\Product\Pricing\ProductPriceCalculationForUser
	 */
	private $productPriceCalculationForUser;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Pricing\ProductPriceCalculation
	 */
	private $productPriceCalculation;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductRepository
	 */
	private $productRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Parameter\ParameterRepository
	 */
	private $parameterRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Image\ImageFacade
	 */
	private $imageFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Localization\Localization
	 */
	private $localization;

	public function __construct(
		ProductPriceCalculationForUser $productPriceCalculationForUser,
		ProductPriceCalculation $productPriceCalculation,
		ProductRepository $productRepository,
		ParameterRepository $parameterRepository,
		ImageFacade $imageFacade,
		Localization $localization
	) {
		$this->productPriceCalculationForUser = $productPriceCalculationForUser;
		$this->productPriceCalculation = $productPriceCalculation;
		$this->productRepository = $productRepository;
		$this->parameterRepository = $parameterRepository;
		$this->imageFacade = $imageFacade;
		$this->localization = $localization;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @return \SS6\ShopBundle\Model\Product\Detail\ProductDetail
	 */
	public function getDetailForProduct(Product $product) {
		return new ProductDetail(
			$product,
			$this->getBasePrice($product),
			$this->getSellingPrice($product),
			$this->getProductDomainsIndexedByDomainId($product),
			$this->getParameters($product),
			$this->getImagesIndexedById($product)
		);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product[] $products
	 * @return \SS6\ShopBundle\Model\Product\Detail\ProductDetail[]
	 */
	public function getDetailsForProducts(array $products) {
		$details = [];

		foreach ($products as $product) {
			$details[] = $this->getDetailForProduct($product);
		}

		return $details;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @return \SS6\ShopBundle\Model\Pricing\Price
	 */
	private function getBasePrice(Product $product) {
		return $this->productPriceCalculation->calculateBasePrice($product);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @return \SS6\ShopBundle\Model\Pricing\Price
	 */
	private function getSellingPrice(Product $product) {
		return $this->productPriceCalculationForUser->calculatePriceForCurrentUser($product);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @return \SS6\ShopBundle\Model\Product\Parameter\ProductParameterValue[]
	 */
	private function getParameters(Product $product) {
		$productParameterValues = $this->parameterRepository->getProductParameterValuesByProductEagerLoaded($product);
		foreach ($productParameterValues as $index => $productParameterValue) {
			$parameter = $productParameterValue->getParameter();
			if ($parameter->getName() === null || $productParameterValue->getLocale() !== $this->localization->getLocale()) {
				unset($productParameterValues[$index]);
			}
		}

		return $productParameterValues;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @return \SS6\ShopBundle\Model\Image\Image[imageId]
	 */
	private function getImagesIndexedById(Product $product) {
		return $this->imageFacade->getImagesByEntityIndexedById($product, null);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @return \SS6\ShopBundle\Model\Product\ProductDomain[]
	 */
	private function getProductDomainsIndexedByDomainId(Product $product) {
		return $this->productRepository->getProductDomainsByProductIndexedByDomainId($product);
	}

}
