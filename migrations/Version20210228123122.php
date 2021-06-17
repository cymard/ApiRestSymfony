<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210228123122 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64945F80CD FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id)');
        // $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64945F80CD ON user (shopping_cart_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64945F80CD');
        $this->addSql('DROP INDEX UNIQ_8D93D64945F80CD ON user');
    }
}
