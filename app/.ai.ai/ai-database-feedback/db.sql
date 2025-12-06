-- --------------------------------------------------------
-- Host:                         D:\www\Herd\@coordova\vs.inertia.vue\database\database.sqlite
-- Versión del servidor:         3.50.4
-- SO del servidor:              
-- HeidiSQL Versión:             12.12.0.7122
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES  */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para database
CREATE DATABASE IF NOT EXISTS "database";
;

-- Volcando estructura para tabla database.categories
CREATE TABLE IF NOT EXISTS "categories" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "description" text, "image" varchar, "color" varchar not null default '#000000', "icon" varchar not null default 'tag', "sort_order" integer not null default '0', "status" tinyint(1) not null default '0', "is_featured" tinyint(1) not null default '0', "meta_title" varchar, "meta_description" varchar, "created_at" datetime, "updated_at" datetime, "deleted_at" datetime);
CREATE UNIQUE INDEX "categories_name_unique" on "categories" ("name");
CREATE UNIQUE INDEX "categories_slug_unique" on "categories" ("slug");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.category_character
CREATE TABLE IF NOT EXISTS "category_character" ("category_id" integer not null, "character_id" integer not null, "elo_rating" integer not null default '1200', "matches_played" integer not null default '0', "wins" integer not null default '0', "losses" integer not null default '0', "win_rate" numeric not null default '0', "highest_rating" integer not null default '1200', "lowest_rating" integer not null default '1200', "rating_deviation" numeric not null default '0', "last_match_at" datetime, "is_featured" tinyint(1) not null default '0', "sort_order" integer not null default '0', "status" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime, "ties" INTEGER NULL DEFAULT '0', foreign key("category_id") references "categories"("id") on delete no action, foreign key("character_id") references "characters"("id") on delete no action, primary key ("category_id", "character_id"));
;
CREATE INDEX "category_character_category_id_elo_rating_index" on "category_character" ("category_id", "elo_rating");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.characters
CREATE TABLE IF NOT EXISTS "characters" ("id" integer primary key autoincrement not null, "fullname" varchar not null, "nickname" varchar, "slug" varchar not null, "bio" text, "dob" date, "gender" integer not null default '0', "nationality" varchar, "occupation" varchar, "picture" varchar not null, "status" tinyint(1) not null default '1', "meta_title" varchar, "meta_description" varchar, "created_at" datetime, "updated_at" datetime, "deleted_at" datetime);
CREATE UNIQUE INDEX "characters_fullname_unique" on "characters" ("fullname");
CREATE UNIQUE INDEX "characters_slug_unique" on "characters" ("slug");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.character_survey
CREATE TABLE IF NOT EXISTS "character_survey" ("character_id" integer not null, "survey_id" integer not null, "survey_matches" integer not null default '0', "survey_wins" integer not null default '0', "survey_losses" integer not null default '0', "survey_ties" integer not null default '0', "is_active" tinyint(1) not null default '1', "sort_order" integer not null default '0', "created_at" datetime, "updated_at" datetime, foreign key("character_id") references "characters"("id") on delete cascade, foreign key("survey_id") references "surveys"("id") on delete no action, primary key ("character_id", "survey_id"));
;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.combinatorics
CREATE TABLE IF NOT EXISTS "combinatorics" ("id" integer primary key autoincrement not null, "survey_id" integer not null, "character1_id" integer not null, "character2_id" integer not null, "total_comparisons" integer not null default '0', "last_used_at" datetime, "status" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime, foreign key("survey_id") references "surveys"("id") on delete cascade, foreign key("character1_id") references "characters"("id") on delete cascade, foreign key("character2_id") references "characters"("id") on delete cascade);
CREATE UNIQUE INDEX "combinatorics_survey_id_character1_id_character2_id_unique" on "combinatorics" ("survey_id", "character1_id", "character2_id");
CREATE INDEX "combinatorics_character1_id_character2_id_index" on "combinatorics" ("character1_id", "character2_id");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.lookups
CREATE TABLE IF NOT EXISTS "lookups" ("id" integer primary key autoincrement not null, "category" varchar not null, "code" varchar not null, "name" varchar not null, "description" text, "metadata" text, "sort_order" integer not null default '0', "is_active" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime, "deleted_at" datetime);
CREATE UNIQUE INDEX "lookups_category_code_unique" on "lookups" ("category", "code");
CREATE INDEX "lookups_category_index" on "lookups" ("category");
CREATE INDEX "lookups_code_index" on "lookups" ("code");
CREATE INDEX "lookups_sort_order_index" on "lookups" ("sort_order");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.surveys
CREATE TABLE IF NOT EXISTS "surveys" ("id" integer primary key autoincrement not null, "category_id" integer not null, "title" varchar not null, "slug" varchar not null, "description" text, "image" varchar, "type" integer not null default '0', "status" tinyint(1) not null default '0', "reverse" tinyint(1) not null default '0', "date_start" date not null, "date_end" date not null, "selection_strategy" varchar not null default 'cooldown', "max_votes_per_user" integer not null default '0', "allow_ties" tinyint(1) not null default '0', "tie_weight" numeric not null default '0.5', "is_featured" tinyint(1) not null default '0', "sort_order" integer not null default '0', "counter" integer not null default '0', "meta_title" varchar, "meta_description" varchar, "created_at" datetime, "updated_at" datetime, "deleted_at" datetime, "total_combinations" INTEGER NULL, foreign key("category_id") references "categories"("id") on delete cascade);
CREATE INDEX "surveys_date_start_index" on "surveys" ("date_start");
CREATE INDEX "surveys_date_end_index" on "surveys" ("date_end");
CREATE UNIQUE INDEX "surveys_slug_unique" on "surveys" ("slug");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.survey_user
CREATE TABLE IF NOT EXISTS "survey_user" ("user_id" integer not null, "survey_id" integer not null, "progress_percentage" numeric not null default '0', "total_votes" integer not null default '0', "completed_at" datetime, "started_at" datetime, "last_activity_at" datetime, "is_completed" tinyint(1) not null default '0', "completion_time" integer, "total_combinations_expected" integer, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete no action, foreign key("survey_id") references "surveys"("id") on delete no action, primary key ("user_id", "survey_id"));
;
CREATE INDEX "survey_user_survey_id_index" on "survey_user" ("survey_id");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.users
CREATE TABLE IF NOT EXISTS "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "email_verified_at" datetime, "password" varchar not null, "avatar" varchar, "timezone" varchar not null default 'UTC', "locale" varchar not null default 'en', "type" integer not null default '0', "status" tinyint(1) not null default '1', "remember_token" varchar, "last_login_at" datetime, "created_at" datetime, "updated_at" datetime, "deleted_at" datetime, "two_factor_secret" text, "two_factor_recovery_codes" text, "two_factor_confirmed_at" datetime);
CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla database.votes
CREATE TABLE IF NOT EXISTS "votes" ("id" integer primary key autoincrement not null, "user_id" integer not null, "combinatoric_id" integer not null, "survey_id" integer not null, "winner_id" integer, "loser_id" integer, "tie_score" numeric, "voted_at" datetime not null default CURRENT_TIMESTAMP, "ip_address" varchar, "user_agent" text, "is_valid" tinyint(1) not null default '1', "notes" text, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade, foreign key("combinatoric_id") references "combinatorics"("id") on delete cascade, foreign key("survey_id") references "surveys"("id") on delete cascade, foreign key("winner_id") references "characters"("id") on delete cascade, foreign key("loser_id") references "characters"("id") on delete cascade);
CREATE INDEX "votes_voted_at_index" on "votes" ("voted_at");
CREATE INDEX "votes_user_id_survey_id_index" on "votes" ("user_id", "survey_id");
CREATE INDEX "votes_survey_id_voted_at_index" on "votes" ("survey_id", "voted_at");

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
