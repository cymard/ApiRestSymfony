<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210227104657 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD45F80CD');
        $this->addSql('DROP INDEX IDX_D34A04AD45F80CD ON product');
        $this->addSql('ALTER TABLE product DROP shopping_cart_id');
        $this->addSql('ALTER TABLE shopping_cart ADD product_id INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD shopping_cart_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD45F80CD FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD45F80CD ON product (shopping_cart_id)');
        $this->addSql('ALTER TABLE shopping_cart DROP product_id');
    }
}