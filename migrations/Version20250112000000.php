<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add Players, Characters, Classes, and Skills tables
 */
final class Version20250112000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tables for players, characters, character classes, and skills';
    }

    public function up(Schema $schema): void
    {
        // Create Players table
        $this->addSql('CREATE TABLE players (
            id SERIAL PRIMARY KEY,
            tg_user_id VARCHAR(255) NOT NULL,
            username VARCHAR(255),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT uniq_players_tg_user_id UNIQUE (tg_user_id)
        )');
        $this->addSql('COMMENT ON COLUMN players.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN players.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_tg_user_id ON players(tg_user_id)');

        // Create Character Classes table
        $this->addSql('CREATE TABLE character_classes (
            id SERIAL PRIMARY KEY,
            parent_id INTEGER,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            requirements JSON,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT fk_character_classes_parent FOREIGN KEY (parent_id) REFERENCES character_classes(id) ON DELETE SET NULL
        )');
        $this->addSql('COMMENT ON COLUMN character_classes.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN character_classes.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_character_classes_parent ON character_classes(parent_id)');

        // Create Player Characters table
        $this->addSql('CREATE TABLE player_characters (
            id SERIAL PRIMARY KEY,
            player_id INTEGER NOT NULL,
            character_class_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            level INTEGER NOT NULL DEFAULT 1,
            completed_quest_ids JSON,
            inventory JSON,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT fk_player_characters_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            CONSTRAINT fk_player_characters_class FOREIGN KEY (character_class_id) REFERENCES character_classes(id) ON DELETE RESTRICT
        )');
        $this->addSql('COMMENT ON COLUMN player_characters.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN player_characters.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_player_characters_player ON player_characters(player_id)');
        $this->addSql('CREATE INDEX idx_player_characters_class ON player_characters(character_class_id)');

        // Create Skills table
        $this->addSql('CREATE TABLE skills (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            unlock_conditions JSON,
            effects JSON,
            position_x DOUBLE PRECISION,
            position_y DOUBLE PRECISION,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN skills.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN skills.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Create Skill Dependencies table (many-to-many for parent-child relationships)
        $this->addSql('CREATE TABLE skill_dependencies (
            child_skill_id INTEGER NOT NULL,
            parent_skill_id INTEGER NOT NULL,
            PRIMARY KEY (child_skill_id, parent_skill_id),
            CONSTRAINT fk_skill_dependencies_child FOREIGN KEY (child_skill_id) REFERENCES skills(id) ON DELETE CASCADE,
            CONSTRAINT fk_skill_dependencies_parent FOREIGN KEY (parent_skill_id) REFERENCES skills(id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_skill_dependencies_child ON skill_dependencies(child_skill_id)');
        $this->addSql('CREATE INDEX idx_skill_dependencies_parent ON skill_dependencies(parent_skill_id)');

        // Create Player Character Skills table (many-to-many for unlocked skills)
        $this->addSql('CREATE TABLE player_character_skills (
            player_character_id INTEGER NOT NULL,
            skill_id INTEGER NOT NULL,
            PRIMARY KEY (player_character_id, skill_id),
            CONSTRAINT fk_player_character_skills_character FOREIGN KEY (player_character_id) REFERENCES player_characters(id) ON DELETE CASCADE,
            CONSTRAINT fk_player_character_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_player_character_skills_character ON player_character_skills(player_character_id)');
        $this->addSql('CREATE INDEX idx_player_character_skills_skill ON player_character_skills(skill_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_character_skills CASCADE');
        $this->addSql('DROP TABLE IF EXISTS skill_dependencies CASCADE');
        $this->addSql('DROP TABLE IF EXISTS skills CASCADE');
        $this->addSql('DROP TABLE IF EXISTS player_characters CASCADE');
        $this->addSql('DROP TABLE IF EXISTS character_classes CASCADE');
        $this->addSql('DROP TABLE IF EXISTS players CASCADE');
    }
}
