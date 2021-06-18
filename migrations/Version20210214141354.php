<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210214141354 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('CREATE TABLE comment_product (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, date DATETIME NOT NULL, comment LONGTEXT NOT NULL, note INT NOT NULL, productId INT DEFAULT NULL, INDEX IDX_203966BD36799605 (productId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // $this->addSql('ALTER TABLE comment_product ADD CONSTRAINT FK_203966BD36799605 FOREIGN KEY (productId) REFERENCES product (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE comment_product');
    }
}
