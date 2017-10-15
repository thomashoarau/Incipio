<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix cascade delete of Facture
 */
class Version20171011071219 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Ondelete set null for montantADeduire
        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY FK_313B5D8CD4F76809');
        $this->addSql('ALTER TABLE Facture ADD CONSTRAINT FK_313B5D8CD4F76809 FOREIGN KEY (montantADeduire_id) REFERENCES FactureDetail (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE FactureDetail DROP FOREIGN KEY FK_82D8557B7F2DEE08');
        $this->addSql('ALTER TABLE FactureDetail ADD CONSTRAINT FK_82D8557B7F2DEE08 FOREIGN KEY (facture_id) REFERENCES Facture (id) ON DELETE CASCADE');

        // Add factureADeduire field to facture detail
        $this->addSql('ALTER TABLE FactureDetail ADD factureADeduire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE FactureDetail ADD CONSTRAINT FK_82D8557BA750AC6D FOREIGN KEY (factureADeduire_id) REFERENCES Facture (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_82D8557BA750AC6D ON FactureDetail (factureADeduire_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY FK_313B5D8CD4F76809');
        $this->addSql('ALTER TABLE Facture ADD CONSTRAINT FK_313B5D8CD4F76809 FOREIGN KEY (montantADeduire_id) REFERENCES FactureDetail (id)');

        $this->addSql('ALTER TABLE FactureDetail DROP FOREIGN KEY FK_82D8557B7F2DEE08');
        $this->addSql('ALTER TABLE FactureDetail ADD CONSTRAINT FK_82D8557B7F2DEE08 FOREIGN KEY (facture_id) REFERENCES Facture (id)');

        $this->addSql('ALTER TABLE FactureDetail DROP FOREIGN KEY FK_82D8557BA750AC6D');
        $this->addSql('DROP INDEX UNIQ_82D8557BA750AC6D ON FactureDetail');
        $this->addSql('ALTER TABLE FactureDetail DROP factureADeduire_id');
    }
}
