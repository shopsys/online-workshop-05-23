<?php

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190523121612 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE article_product (
                article_id INT NOT NULL,
                product_id INT NOT NULL,
                PRIMARY KEY(article_id, product_id)
            )');
        $this->sql('CREATE INDEX IDX_3E98401A7294869C ON article_product (article_id)');
        $this->sql('CREATE INDEX IDX_3E98401A4584665A ON article_product (product_id)');
        $this->sql('
            ALTER TABLE
                article_product
            ADD
                CONSTRAINT FK_3E98401A7294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                article_product
            ADD
                CONSTRAINT FK_3E98401A4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
