<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831192301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE like_comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, comment_id INTEGER NOT NULL, CONSTRAINT FK_C7F9184FA76ED395 FOREIGN KEY (user_id) REFERENCES symfony_demo_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C7F9184FF8697D13 FOREIGN KEY (comment_id) REFERENCES symfony_demo_comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C7F9184FA76ED395 ON like_comment (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C7F9184FF8697D13 ON like_comment (comment_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE like_post (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, post_id INTEGER NOT NULL, CONSTRAINT FK_83FFB0F3A76ED395 FOREIGN KEY (user_id) REFERENCES symfony_demo_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_83FFB0F34B89032C FOREIGN KEY (post_id) REFERENCES symfony_demo_post (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_83FFB0F3A76ED395 ON like_post (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_83FFB0F34B89032C ON like_post (post_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE like_comment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE like_post
        SQL);
    }
}
