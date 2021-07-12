<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210712072656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_product DROP INDEX IDX_2530ADE64584665A, ADD UNIQUE INDEX UNIQ_2530ADE64584665A (product_id)');
        $this->addSql('ALTER TABLE order_product ADD image LONGTEXT DEFAULT NULL, CHANGE product_id product_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_product DROP INDEX UNIQ_2530ADE64584665A, ADD INDEX IDX_2530ADE64584665A (product_id)');
        $this->addSql('ALTER TABLE order_product DROP image, CHANGE product_id product_id INT NOT NULL');
    }
}
