<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210303112011 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_product ADD CONSTRAINT FK_2890CCAAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2890CCAAA76ED395 ON cart_product (user_id)');

        // $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64925EE16A8');
        // $this->addSql('DROP INDEX IDX_8D93D64925EE16A8 ON user');
        // $this->addSql('ALTER TABLE user DROP cart_product_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_product DROP FOREIGN KEY FK_2890CCAAA76ED395');
        $this->addSql('DROP INDEX IDX_2890CCAAA76ED395 ON cart_product');
        $this->addSql('ALTER TABLE user ADD cart_product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64925EE16A8 FOREIGN KEY (cart_product_id) REFERENCES cart_product (id)');
        $this->addSql('CREATE INDEX IDX_8D93D64925EE16A8 ON user (cart_product_id)');
    }
}
