<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227092303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE products_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE digital_products (id INT NOT NULL, download_url VARCHAR(255) DEFAULT NULL, file_size INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE physical_products (id INT NOT NULL, sku VARCHAR(50) DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE products (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, price DOUBLE PRECISION NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE digital_products ADD CONSTRAINT FK_534F319FBF396750 FOREIGN KEY (id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE physical_products ADD CONSTRAINT FK_F671BE87BF396750 FOREIGN KEY (id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE products_id_seq CASCADE');
        $this->addSql('ALTER TABLE digital_products DROP CONSTRAINT FK_534F319FBF396750');
        $this->addSql('ALTER TABLE physical_products DROP CONSTRAINT FK_F671BE87BF396750');
        $this->addSql('DROP TABLE digital_products');
        $this->addSql('DROP TABLE physical_products');
        $this->addSql('DROP TABLE products');
    }
}
