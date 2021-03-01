<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210228125032 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_product DROP shopping_cart_id');
        $this->addSql('ALTER TABLE shopping_cart ADD cart_product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shopping_cart ADD CONSTRAINT FK_72AAD4F625EE16A8 FOREIGN KEY (cart_product_id) REFERENCES cart_product (id)');
        $this->addSql('CREATE INDEX IDX_72AAD4F625EE16A8 ON shopping_cart (cart_product_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_product ADD shopping_cart_id INT NOT NULL');
        $this->addSql('ALTER TABLE shopping_cart DROP FOREIGN KEY FK_72AAD4F625EE16A8');
        $this->addSql('DROP INDEX IDX_72AAD4F625EE16A8 ON shopping_cart');
        $this->addSql('ALTER TABLE shopping_cart DROP cart_product_id');
    }
}
