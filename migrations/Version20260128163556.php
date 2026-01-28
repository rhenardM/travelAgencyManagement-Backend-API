<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128163556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE identity_proof (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, file_path VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_size INT DEFAULT NULL, status VARCHAR(20) NOT NULL, uploaded_at DATETIME NOT NULL, client_id INT NOT NULL, INDEX IDX_11A20A2D19EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE identity_proof ADD CONSTRAINT FK_11A20A2D19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('DROP INDEX UNIQ_C744045596901F54 ON client');
        $this->addSql('ALTER TABLE client ADD address VARCHAR(255) NOT NULL, DROP first_name, DROP adresse, DROP identity_document_path, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE number phone VARCHAR(20) NOT NULL, CHANGE profile_picture profile_picture_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7440455444F97DD ON client (phone)');
        $this->addSql('ALTER TABLE user DROP name, DROP last_name, DROP profile_picture');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE identity_proof DROP FOREIGN KEY FK_11A20A2D19EB6921');
        $this->addSql('DROP TABLE identity_proof');
        $this->addSql('DROP INDEX UNIQ_C7440455444F97DD ON client');
        $this->addSql('ALTER TABLE client ADD adresse VARCHAR(255) NOT NULL, ADD identity_document_path VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE address first_name VARCHAR(255) NOT NULL, CHANGE phone number VARCHAR(20) NOT NULL, CHANGE profile_picture_path profile_picture VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C744045596901F54 ON client (number)');
        $this->addSql('ALTER TABLE `user` ADD name VARCHAR(100) DEFAULT NULL, ADD last_name VARCHAR(100) DEFAULT NULL, ADD profile_picture VARCHAR(255) DEFAULT NULL');
    }
}
