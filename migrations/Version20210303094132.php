<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210303094132 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart_product_user (cart_product_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_76F4668125EE16A8 (cart_product_id), INDEX IDX_76F46681A76ED395 (user_id), PRIMARY KEY(cart_product_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cart_product_product (cart_product_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_7BE599ED25EE16A8 (cart_product_id), INDEX IDX_7BE599ED4584665A (product_id), PRIMARY KEY(cart_product_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cart_product_user ADD CONSTRAINT FK_76F4668125EE16A8 FOREIGN KEY (cart_product_id) REFERENCES cart_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_product_user ADD CONSTRAINT FK_76F46681A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_product_product ADD CONSTRAINT FK_7BE599ED25EE16A8 FOREIGN KEY (cart_product_id) REFERENCES cart_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_product_product ADD CONSTRAINT FK_7BE599ED4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD25EE16A8');
        $this->addSql('DROP INDEX IDX_D34A04AD25EE16A8 ON product');
        $this->addSql('ALTER TABLE product DROP cart_product_id');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64925EE16A8');
        $this->addSql('DROP INDEX IDX_8D93D64925EE16A8 ON user');
        $this->addSql('ALTER TABLE user DROP cart_product_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE cart_product_user');
        $this->addSql('DROP TABLE cart_product_product');
        $this->addSql('ALTER TABLE product ADD cart_product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD25EE16A8 FOREIGN KEY (cart_product_id) REFERENCES cart_product (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD25EE16A8 ON product (cart_product_id)');
        $this->addSql('ALTER TABLE user ADD cart_product_id INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64925EE16A8 FOREIGN KEY (cart_product_id) REFERENCES cart_product (id)');
        $this->addSql('CREATE INDEX IDX_8D93D64925EE16A8 ON user (cart_product_id)');
    }
}
