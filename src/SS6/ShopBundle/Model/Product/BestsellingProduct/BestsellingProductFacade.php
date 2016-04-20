<?php

namespace SS6\ShopBundle\Model\Product\BestsellingProduct;

use DateTime;
use Doctrine\ORM\EntityManager;
use SS6\ShopBundle\Model\Category\Category;
use SS6\ShopBundle\Model\Pricing\Group\PricingGroup;
use SS6\ShopBundle\Model\Product\BestsellingProduct\BestsellingProductRepository;
use SS6\ShopBundle\Model\Product\BestsellingProduct\BestsellingProductService;
use SS6\ShopBundle\Model\Product\Detail\ProductDetailFactory;

class BestsellingProductFacade {

	const MAX_RESULTS = 10;
	const ORDERS_CREATED_AT_LIMIT = '-1 month';
	const MAX_SHOW_RESULTS = 3;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var \SS6\ShopBundle\Model\Product\BestsellingProduct\BestsellingProductRepository
	 */
	private $bestsellingProductRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Detail\ProductDetailFactory
	 */
	private $productDetailFactory;

	/**
	 * @var \SS6\ShopBundle\Model\Product\BestsellingProduct\BestsellingProductService
	 */
	private $bestsellingProductService;

	public function __construct(
		EntityManager $em,
		BestsellingProductRepository $bestsellingProductRepository,
		ProductDetailFactory $productDetailFactory,
		BestsellingProductService $bestsellingProductService
	) {
		$this->em = $em;
		$this->bestsellingProductRepository = $bestsellingProductRepository;
		$this->productDetailFactory = $productDetailFactory;
		$this->bestsellingProductService = $bestsellingProductService;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param int $domainId
	 * @param \SS6\ShopBundle\Model\Product\Product[] $bestsellingProducts
	 */
	public function edit(Category $category, $domainId, array $bestsellingProducts) {
		$toDelete = $this->bestsellingProductRepository->getManualBestsellingProductsByCategoryAndDomainId($category, $domainId);
		foreach ($toDelete as $item) {
			$this->em->remove($item);
		}
		$this->em->flush();

		foreach ($bestsellingProducts as $position => $product) {
			if ($product !== null) {
				$manualBestsellingProduct = new ManualBestsellingProduct($domainId, $category, $product, $position);
				$this->em->persist($manualBestsellingProduct);
			}
		}
		$this->em->flush();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param int $domainId
	 * @return \SS6\ShopBundle\Model\Product\Product[]
	 */
	public function getBestsellingProductsIndexedByPosition($category, $domainId) {
		$bestsellingProducts = $this->bestsellingProductRepository->getManualBestsellingProductsByCategoryAndDomainId(
			$category,
			$domainId
		);

		$products = [];
		foreach ($bestsellingProducts as $key => $bestsellingProduct) {
			$products[$key] = $bestsellingProduct->getProduct();

		}

		return $products;
	}

	/**
	 * @param int $domainId
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @return \SS6\ShopBundle\Model\Product\Detail\ProductDetail[]
	 */
	public function getAllOfferedProductDetails($domainId, Category $category, PricingGroup $pricingGroup) {
		$bestsellingProducts = $this->bestsellingProductRepository->getOfferedManualBestsellingProducts(
			$domainId,
			$category,
			$pricingGroup
		);

		$manualBestsellingProductsIndexedByPosition = [];
		foreach ($bestsellingProducts as $bestsellingProduct) {
			$manualBestsellingProductsIndexedByPosition[$bestsellingProduct->getPosition()] = $bestsellingProduct->getProduct();
		}

		$automaticBestsellingProducts = $this->bestsellingProductRepository->getOfferedAutomaticBestsellingProducts(
			$domainId,
			$category,
			$pricingGroup,
			new DateTime(self::ORDERS_CREATED_AT_LIMIT),
			self::MAX_RESULTS
		);

		$combinedBestsellingProducts = $this->bestsellingProductService->combineManualAndAutomaticBestsellingProducts(
			$manualBestsellingProductsIndexedByPosition,
			$automaticBestsellingProducts,
			self::MAX_RESULTS
		);

		return $this->productDetailFactory->getDetailsForProducts($combinedBestsellingProducts);
	}

	/**
	 * @param int $domainId
	 * @return int[categoryId]
	 */
	public function getManualBestsellingProductCountsInCategories($domainId) {
		return $this->bestsellingProductRepository->getManualBestsellingProductCountsInCategories($domainId);
	}

}
