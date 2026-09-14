<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20240914000001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS app_user (id SERIAL PRIMARY KEY, email VARCHAR(180) NOT NULL UNIQUE, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at TIMESTAMP NOT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS company (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, rut VARCHAR(20) DEFAULT NULL, created_at TIMESTAMP DEFAULT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS owner (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS property (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, company_id INT DEFAULT NULL, owner_id INT DEFAULT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS document (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, path VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS settlement (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, period VARCHAR(20) DEFAULT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS settlement_item (id SERIAL PRIMARY KEY, description VARCHAR(255) NOT NULL, amount NUMERIC(10,2) DEFAULT 0, settlement_id INT DEFAULT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS invoice (id SERIAL PRIMARY KEY, number VARCHAR(100) NOT NULL, amount NUMERIC(10,2) DEFAULT 0)');
        $this->addSql('CREATE TABLE IF NOT EXISTS invoice_settlement (id SERIAL PRIMARY KEY, invoice_id INT DEFAULT NULL, settlement_id INT DEFAULT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGSERIAL PRIMARY KEY, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP NOT NULL, available_at TIMESTAMP NOT NULL, delivered_at TIMESTAMP DEFAULT NULL)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_queue ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_avail ON messenger_messages (available_at)');
    }
    public function down(Schema $schema): void {}
}
