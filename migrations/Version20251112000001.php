<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduce players, classes, skills, and character progression tables.
 */
final class Version20251112000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add players, player classes, skills, and character progression tables with dependency graphs.';
    }

    public function up(Schema $schema): void
    {
        // Players
        $this->addSql('CREATE TABLE players (
            id SERIAL PRIMARY KEY,
            tg_user_id VARCHAR(64) NOT NULL,
            display_name VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN players.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN players.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_players_tg_user_id ON players (tg_user_id)');

        // Player classes
        $this->addSql('CREATE TABLE player_classes (
            id SERIAL PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            description TEXT DEFAULT NULL,
            parent_id INT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN player_classes.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN player_classes.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_player_classes_name ON player_classes (name)');
        $this->addSql('CREATE INDEX idx_player_classes_parent ON player_classes (parent_id)');

        // Skills
        $this->addSql('CREATE TABLE skills (
            id SERIAL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            tier VARCHAR(50) NOT NULL,
            description TEXT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            extra_requirements JSON DEFAULT NULL,
            required_level INT DEFAULT NULL,
            position_x DOUBLE PRECISION DEFAULT NULL,
            position_y DOUBLE PRECISION DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN skills.metadata IS \'(DC2Type:json)\'');
        $this->addSql('COMMENT ON COLUMN skills.extra_requirements IS \'(DC2Type:json)\'');
        $this->addSql('COMMENT ON COLUMN skills.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN skills.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_skills_name ON skills (name)');

        // Player characters
        $this->addSql('CREATE TABLE player_characters (
            id SERIAL PRIMARY KEY,
            player_id INT NOT NULL,
            class_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            level INT NOT NULL,
            experience INT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN player_characters.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN player_characters.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_player_characters_player ON player_characters (player_id)');
        $this->addSql('CREATE INDEX idx_player_characters_class ON player_characters (class_id)');

        // Character skills
        $this->addSql('CREATE TABLE character_skills (
            id SERIAL PRIMARY KEY,
            character_id INT NOT NULL,
            skill_id INT NOT NULL,
            unlocked_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('COMMENT ON COLUMN character_skills.unlocked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_character_skill ON character_skills (character_id, skill_id)');
        $this->addSql('CREATE INDEX idx_character_skills_character ON character_skills (character_id)');
        $this->addSql('CREATE INDEX idx_character_skills_skill ON character_skills (skill_id)');

        // Join tables
        $this->addSql('CREATE TABLE skill_required_classes (
            skill_id INT NOT NULL,
            player_class_id INT NOT NULL,
            PRIMARY KEY(skill_id, player_class_id)
        )');
        $this->addSql('CREATE INDEX idx_skill_required_classes_class ON skill_required_classes (player_class_id)');
        $this->addSql('CREATE TABLE skill_required_quests (
            skill_id INT NOT NULL,
            quest_id INT NOT NULL,
            PRIMARY KEY(skill_id, quest_id)
        )');
        $this->addSql('CREATE INDEX idx_skill_required_quests_quest ON skill_required_quests (quest_id)');
        $this->addSql('CREATE TABLE skill_prerequisites (
            skill_id INT NOT NULL,
            prerequisite_id INT NOT NULL,
            PRIMARY KEY(skill_id, prerequisite_id)
        )');
        $this->addSql('CREATE INDEX idx_skill_prereq_prerequisite ON skill_prerequisites (prerequisite_id)');
        $this->addSql('CREATE TABLE character_completed_quests (
            character_id INT NOT NULL,
            quest_id INT NOT NULL,
            PRIMARY KEY(character_id, quest_id)
        )');
        $this->addSql('CREATE INDEX idx_character_completed_quests_quest ON character_completed_quests (quest_id)');

        // Foreign keys
        $this->addSql('ALTER TABLE player_classes ADD CONSTRAINT fk_player_classes_parent FOREIGN KEY (parent_id) REFERENCES player_classes (id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE player_characters ADD CONSTRAINT fk_player_characters_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player_characters ADD CONSTRAINT fk_player_characters_class FOREIGN KEY (class_id) REFERENCES player_classes (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE character_skills ADD CONSTRAINT fk_character_skills_character FOREIGN KEY (character_id) REFERENCES player_characters (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE character_skills ADD CONSTRAINT fk_character_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_required_classes ADD CONSTRAINT fk_skill_required_classes_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_required_classes ADD CONSTRAINT fk_skill_required_classes_class FOREIGN KEY (player_class_id) REFERENCES player_classes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_required_quests ADD CONSTRAINT fk_skill_required_quests_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_required_quests ADD CONSTRAINT fk_skill_required_quests_quest FOREIGN KEY (quest_id) REFERENCES quests (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_prerequisites ADD CONSTRAINT fk_skill_prerequisites_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_prerequisites ADD CONSTRAINT fk_skill_prerequisites_parent FOREIGN KEY (prerequisite_id) REFERENCES skills (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE character_completed_quests ADD CONSTRAINT fk_character_completed_quests_character FOREIGN KEY (character_id) REFERENCES player_characters (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE character_completed_quests ADD CONSTRAINT fk_character_completed_quests_quest FOREIGN KEY (quest_id) REFERENCES quests (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill_prerequisites DROP CONSTRAINT fk_skill_prerequisites_parent');
        $this->addSql('ALTER TABLE skill_prerequisites DROP CONSTRAINT fk_skill_prerequisites_skill');
        $this->addSql('ALTER TABLE skill_required_classes DROP CONSTRAINT fk_skill_required_classes_class');
        $this->addSql('ALTER TABLE skill_required_classes DROP CONSTRAINT fk_skill_required_classes_skill');
        $this->addSql('ALTER TABLE skill_required_quests DROP CONSTRAINT fk_skill_required_quests_quest');
        $this->addSql('ALTER TABLE skill_required_quests DROP CONSTRAINT fk_skill_required_quests_skill');
        $this->addSql('ALTER TABLE character_completed_quests DROP CONSTRAINT fk_character_completed_quests_quest');
        $this->addSql('ALTER TABLE character_completed_quests DROP CONSTRAINT fk_character_completed_quests_character');
        $this->addSql('ALTER TABLE character_skills DROP CONSTRAINT fk_character_skills_skill');
        $this->addSql('ALTER TABLE character_skills DROP CONSTRAINT fk_character_skills_character');
        $this->addSql('ALTER TABLE player_characters DROP CONSTRAINT fk_player_characters_class');
        $this->addSql('ALTER TABLE player_characters DROP CONSTRAINT fk_player_characters_player');
        $this->addSql('ALTER TABLE player_classes DROP CONSTRAINT fk_player_classes_parent');

        $this->addSql('DROP TABLE IF EXISTS character_completed_quests');
        $this->addSql('DROP TABLE IF EXISTS skill_prerequisites');
        $this->addSql('DROP TABLE IF EXISTS skill_required_quests');
        $this->addSql('DROP TABLE IF EXISTS skill_required_classes');
        $this->addSql('DROP TABLE IF EXISTS character_skills');
        $this->addSql('DROP TABLE IF EXISTS player_characters');
        $this->addSql('DROP TABLE IF EXISTS skills');
        $this->addSql('DROP TABLE IF EXISTS player_classes');
        $this->addSql('DROP TABLE IF EXISTS players');
    }
}

