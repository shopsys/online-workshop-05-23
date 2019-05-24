<?php

namespace Shopsys\ShopBundle\Model\Article\ArticleProduct;

use Shopsys\ShopBundle\Model\Article\Article;

class ArticleProductFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleProduct\ArticleProductRepository
     */
    private $articleProductRepository;

    /**
     * @param \Shopsys\ShopBundle\Model\Article\ArticleProduct\ArticleProductRepository $articleProductRepository
     */
    public function __construct(ArticleProductRepository $articleProductRepository)
    {
        $this->articleProductRepository = $articleProductRepository;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Article\Article $article
     * @return \Shopsys\ShopBundle\Model\Article\ArticleProduct\ArticleProduct[]
     */
    public function getArticleProductsByArticle(Article $article)
    {
        return $this->articleProductRepository->getArticleProductsByArticle($article);
    }
}
