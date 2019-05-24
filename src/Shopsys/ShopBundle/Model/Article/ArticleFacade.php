<?php

namespace Shopsys\ShopBundle\Model\Article;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Article\ArticleData;
use Shopsys\FrameworkBundle\Model\Article\ArticleFacade as BaseArticleFacade;
use Shopsys\FrameworkBundle\Model\Article\ArticleFactoryInterface;
use Shopsys\FrameworkBundle\Model\Article\ArticleRepository;
use Shopsys\ShopBundle\Model\Article\ArticleProduct\ArticleProduct;
use Shopsys\ShopBundle\Model\Article\ArticleProduct\ArticleProductFacade;

class ArticleFacade extends BaseArticleFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleProduct\ArticleProductFacade
     */
    private $articleProductFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleRepository $articleRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleFactoryInterface $articleFactory
     * @param \Shopsys\ShopBundle\Model\Article\ArticleProduct\ArticleProductFacade $articleProductFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ArticleRepository $articleRepository,
        Domain $domain,
        FriendlyUrlFacade $friendlyUrlFacade,
        ArticleFactoryInterface $articleFactory,
        ArticleProductFacade $articleProductFacade
    ) {
        parent::__construct($em, $articleRepository, $domain, $friendlyUrlFacade, $articleFactory);
        $this->articleProductFacade = $articleProductFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Article\ArticleData $articleData
     * @return \Shopsys\FrameworkBundle\Model\Article\Article
     */
    public function create(ArticleData $articleData)
    {
        $article = parent::create($articleData);
        $products = $articleData->products;
        $this->refreshArticleProducts($products, $article);

        return $article;
    }

    /**
     * @param int $articleId
     * @param \Shopsys\ShopBundle\Model\Article\ArticleData $articleData
     * @return \Shopsys\FrameworkBundle\Model\Article\Article
     */
    public function edit($articleId, ArticleData $articleData)
    {
        $article = parent::edit($articleId, $articleData);
        $products = $articleData->products;
        $this->refreshArticleProducts($products, $article);

        return $article;
    }

    /**
     * @param array $products
     * @param \Shopsys\ShopBundle\Model\Article\Article $article
     */
    private function refreshArticleProducts(array $products, Article $article)
    {
        $oldArticleProducts = $this->articleProductFacade->getArticleProductsByArticle($article);
        foreach ($oldArticleProducts as $oldArticleProduct) {
            $this->em->remove($oldArticleProduct);
        }
        $this->em->flush($oldArticleProducts);

        $toFlush = [];
        foreach ($products as $product) {
            $articleProduct = new ArticleProduct($article, $product);
            $this->em->persist($articleProduct);
            $toFlush[] = $articleProduct;
        }
        $this->em->flush($toFlush);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Article\Article $article
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsByArticle(Article $article)
    {
        $articleProducts = $this->articleProductFacade->getArticleProductsByArticle($article);

        $products = [];
        foreach ($articleProducts as $articleProduct) {
            $products[] = $articleProduct->getProduct();
        }

        return $products;
    }
}
