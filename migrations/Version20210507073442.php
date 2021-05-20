<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210507073442 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE66D128938');
        $this->addSql('DROP INDEX IDX_2530ADE66D128938 ON order_product');
        $this->addSql('ALTER TABLE order_product CHANGE user_order_id order_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE68D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_2530ADE68D9F6D38 ON order_product (order_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE68D9F6D38');
        $this->addSql('DROP INDEX IDX_2530ADE68D9F6D38 ON order_product');
        $this->addSql('ALTER TABLE order_product CHANGE order_id user_order_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE66D128938 FOREIGN KEY (user_order_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_2530ADE66D128938 ON order_product (user_order_id)');
    }
}
