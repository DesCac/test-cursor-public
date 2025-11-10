<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial database schema for RPG Quest & NPC Service
 */
final class Version20231110000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema for NPCs, Quests, and their logic nodes';
    }

    public function up(Schema $schema): void
    {
        // Create NPCs table
        $this->addSql('CREATE TABLE npcs (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN npcs.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN npcs.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Create Dialog Nodes table
        $this->addSql('CREATE TABLE dialog_nodes (
            id SERIAL PRIMARY KEY,
            npc_id INTEGER NOT NULL,
            node_type VARCHAR(50) NOT NULL,
            text TEXT,
            conditions JSON,
            position_x DOUBLE PRECISION,
            position_y DOUBLE PRECISION,
            CONSTRAINT fk_dialog_nodes_npc FOREIGN KEY (npc_id) REFERENCES npcs(id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_dialog_nodes_npc ON dialog_nodes(npc_id)');

        // Create Dialog Connections table
        $this->addSql('CREATE TABLE dialog_connections (
            id SERIAL PRIMARY KEY,
            source_node_id INTEGER NOT NULL,
            target_node_id INTEGER NOT NULL,
            choice_text VARCHAR(500),
            conditions JSON,
            CONSTRAINT fk_dialog_connections_source FOREIGN KEY (source_node_id) REFERENCES dialog_nodes(id) ON DELETE CASCADE,
            CONSTRAINT fk_dialog_connections_target FOREIGN KEY (target_node_id) REFERENCES dialog_nodes(id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_dialog_connections_source ON dialog_connections(source_node_id)');
        $this->addSql('CREATE INDEX idx_dialog_connections_target ON dialog_connections(target_node_id)');

        // Create Quests table
        $this->addSql('CREATE TABLE quests (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            objectives JSON,
            rewards JSON,
            requirements JSON,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN quests.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quests.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Create Quest Nodes table
        $this->addSql('CREATE TABLE quest_nodes (
            id SERIAL PRIMARY KEY,
            quest_id INTEGER NOT NULL,
            node_type VARCHAR(50) NOT NULL,
            data JSON,
            conditions JSON,
            position_x DOUBLE PRECISION,
            position_y DOUBLE PRECISION,
            CONSTRAINT fk_quest_nodes_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_quest_nodes_quest ON quest_nodes(quest_id)');

        // Create Quest Connections table
        $this->addSql('CREATE TABLE quest_connections (
            id SERIAL PRIMARY KEY,
            source_node_id INTEGER NOT NULL,
            target_node_id INTEGER NOT NULL,
            conditions JSON,
            CONSTRAINT fk_quest_connections_source FOREIGN KEY (source_node_id) REFERENCES quest_nodes(id) ON DELETE CASCADE,
            CONSTRAINT fk_quest_connections_target FOREIGN KEY (target_node_id) REFERENCES quest_nodes(id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_quest_connections_source ON quest_connections(source_node_id)');
        $this->addSql('CREATE INDEX idx_quest_connections_target ON quest_connections(target_node_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS quest_connections CASCADE');
        $this->addSql('DROP TABLE IF EXISTS quest_nodes CASCADE');
        $this->addSql('DROP TABLE IF EXISTS quests CASCADE');
        $this->addSql('DROP TABLE IF EXISTS dialog_connections CASCADE');
        $this->addSql('DROP TABLE IF EXISTS dialog_nodes CASCADE');
        $this->addSql('DROP TABLE IF EXISTS npcs CASCADE');
    }
}
