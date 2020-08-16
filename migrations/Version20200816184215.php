<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200816184215 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE channels_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE chat_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vacancies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE channels (id INT NOT NULL, channel_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE chat (id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, chat_id BIGINT NOT NULL, vacancy_category_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_659DF2AA1A9A7125 ON chat (chat_id)');
        $this->addSql('CREATE TABLE vacancies (id INT NOT NULL, channel_id INT NOT NULL, vacancy_id INT NOT NULL, is_sent BOOLEAN NOT NULL, got_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_99165A5972F5A1AA ON vacancies (channel_id)');
        $this->addSql('ALTER TABLE vacancies ADD CONSTRAINT FK_99165A5972F5A1AA FOREIGN KEY (channel_id) REFERENCES channels (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE vacancies DROP CONSTRAINT FK_99165A5972F5A1AA');
        $this->addSql('DROP SEQUENCE channels_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE chat_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vacancies_id_seq CASCADE');
        $this->addSql('DROP TABLE channels');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE vacancies');
    }
}
