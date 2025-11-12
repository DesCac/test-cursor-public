<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Expand domain to players, character classes, and skill trees.
 */
final class Version20251112000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create players, characters, classes, skills and their relations for skill trees';
    }

    public function up(Schema $schema): void
    {
        // Players
        $this->addSql('CREATE TABLE players (
            id SERIAL PRIMARY KEY,
            tg_user_id VARCHAR(64) NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_players_tg_user_id ON players (tg_user_id)');
        $this->addSql('COMMENT ON COLUMN players.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN players.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Player classes
        $this->addSql('CREATE TABLE player_classes (
            id SERIAL PRIMARY KEY,
            parent_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            CONSTRAINT fk_player_classes_parent FOREIGN KEY (parent_id) REFERENCES player_classes (id) ON DELETE SET NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_player_classes_slug ON player_classes (slug)');
        $this->addSql('CREATE INDEX idx_player_classes_parent ON player_classes (parent_id)');

        // Characters
        $this->addSql('CREATE TABLE player_characters (
            id SERIAL PRIMARY KEY,
            player_id INT NOT NULL,
            player_class_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            level INT NOT NULL,
            experience INT NOT NULL,
            attributes JSON DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT fk_player_characters_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE,
            CONSTRAINT fk_player_characters_class FOREIGN KEY (player_class_id) REFERENCES player_classes (id)
        )');
        $this->addSql('CREATE INDEX idx_player_characters_player ON player_characters (player_id)');
        $this->addSql('CREATE INDEX idx_player_characters_class ON player_characters (player_class_id)');
        $this->addSql('COMMENT ON COLUMN player_characters.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN player_characters.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Skills
        $this->addSql('CREATE TABLE skills (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            required_level INT DEFAULT NULL,
            availability_rules JSON DEFAULT NULL,
            position_x DOUBLE PRECISION DEFAULT NULL,
            position_y DOUBLE PRECISION DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_skills_slug ON skills (slug)');
        $this->addSql('COMMENT ON COLUMN skills.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN skills.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Skill links for dependencies
        $this->addSql('CREATE TABLE skill_links (
            id SERIAL PRIMARY KEY,
            parent_skill_id INT NOT NULL,
            child_skill_id INT NOT NULL,
            requires_all_parents BOOLEAN DEFAULT TRUE NOT NULL,
            metadata JSON DEFAULT NULL,
            CONSTRAINT fk_skill_links_parent FOREIGN KEY (parent_skill_id) REFERENCES skills (id) ON DELETE CASCADE,
            CONSTRAINT fk_skill_links_child FOREIGN KEY (child_skill_id) REFERENCES skills (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_skill_link_parent_child ON skill_links (parent_skill_id, child_skill_id)');
        $this->addSql('CREATE INDEX idx_skill_links_parent ON skill_links (parent_skill_id)');
        $this->addSql('CREATE INDEX idx_skill_links_child ON skill_links (child_skill_id)');

        // Character skills unlocks
        $this->addSql('CREATE TABLE character_skills (
            id SERIAL PRIMARY KEY,
            character_id INT NOT NULL,
            skill_id INT NOT NULL,
            unlocked_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            unlock_context JSON DEFAULT NULL,
            CONSTRAINT fk_character_skills_character FOREIGN KEY (character_id) REFERENCES player_characters (id) ON DELETE CASCADE,
            CONSTRAINT fk_character_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_character_skills_pair ON character_skills (character_id, skill_id)');
        $this->addSql('CREATE INDEX idx_character_skills_character ON character_skills (character_id)');
        $this->addSql('CREATE INDEX idx_character_skills_skill ON character_skills (skill_id)');
        $this->addSql('COMMENT ON COLUMN character_skills.unlocked_at IS \'(DC2Type:datetime_immutable)\'');

        // Skill required classes (pivot)
        $this->addSql('CREATE TABLE skill_required_classes (
            skill_id INT NOT NULL,
            player_class_id INT NOT NULL,
            PRIMARY KEY(skill_id, player_class_id),
            CONSTRAINT fk_skill_required_classes_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE,
            CONSTRAINT fk_skill_required_classes_class FOREIGN KEY (player_class_id) REFERENCES player_classes (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_skill_required_classes_skill ON skill_required_classes (skill_id)');
        $this->addSql('CREATE INDEX idx_skill_required_classes_class ON skill_required_classes (player_class_id)');

        // Skill required quests (pivot)
        $this->addSql('CREATE TABLE skill_required_quests (
            skill_id INT NOT NULL,
            quest_id INT NOT NULL,
            PRIMARY KEY(skill_id, quest_id),
            CONSTRAINT fk_skill_required_quests_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE,
            CONSTRAINT fk_skill_required_quests_quest FOREIGN KEY (quest_id) REFERENCES quests (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_skill_required_quests_skill ON skill_required_quests (skill_id)');
        $this->addSql('CREATE INDEX idx_skill_required_quests_quest ON skill_required_quests (quest_id)');

        // Completed quests per character
        $this->addSql('CREATE TABLE character_completed_quests (
            character_id INT NOT NULL,
            quest_id INT NOT NULL,
            PRIMARY KEY(character_id, quest_id),
            CONSTRAINT fk_character_completed_quests_character FOREIGN KEY (character_id) REFERENCES player_characters (id) ON DELETE CASCADE,
            CONSTRAINT fk_character_completed_quests_quest FOREIGN KEY (quest_id) REFERENCES quests (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_character_completed_quests_character ON character_completed_quests (character_id)');
        $this->addSql('CREATE INDEX idx_character_completed_quests_quest ON character_completed_quests (quest_id)');
        // no comment needed (composite bridge table)
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS character_completed_quests CASCADE');
        $this->addSql('DROP TABLE IF EXISTS skill_required_quests CASCADE');
        $this->addSql('DROP TABLE IF EXISTS skill_required_classes CASCADE');
        $this->addSql('DROP TABLE IF EXISTS character_skills CASCADE');
        $this->addSql('DROP TABLE IF EXISTS skill_links CASCADE');
        $this->addSql('DROP TABLE IF EXISTS skills CASCADE');
        $this->addSql('DROP TABLE IF EXISTS player_characters CASCADE');
        $this->addSql('DROP TABLE IF EXISTS player_classes CASCADE');
        $this->addSql('DROP TABLE IF EXISTS players CASCADE');
    }
}

