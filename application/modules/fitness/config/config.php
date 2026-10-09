<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Config;

use Ilch\Config\Database;
use Ilch\Config\Install;
use Modules\Admin\Mappers\Box as BoxMapper;
use Modules\Admin\Models\Box as BoxModel;
use Modules\Fitness\Mappers\Milestone as MilestoneMapper;

class Config extends Install
{
    public array $config = [
        'key' => 'fitness',
        'version' => '1.1.0',
        'icon_small' => 'fa-solid fa-dumbbell',
        'author' => 'PrivatePHPCoder',
        'languages' => [
            'de_DE' => [
                'name' => 'Fitness',
                'description' => 'Übungen, Workouts und Trainingsprogramme mit Teilnahme, Fortschritt und Meilensteinen.',
            ],
            'en_EN' => [
                'name' => 'Fitness',
                'description' => 'Exercises, workouts and training programs with enrollment, progress and milestones.',
            ],
        ],
        'boxes' => [
            'progress' => [
                'de_DE' => [
                    'name' => 'Mein Fitness-Fortschritt',
                ],
                'en_EN' => [
                    'name' => 'My fitness progress',
                ],
            ],
        ],
        'ilchCore' => '2.2.20',
        'phpVersion' => '8.1'
    ];

    /**
     * Tables of this module without prefix. Children come before their parents,
     * so they can be dropped in this order without violating foreign keys.
     *
     * @var string[]
     */
    private const TABLES = [
        'fitness_user_milestones',
        'fitness_milestones',
        'fitness_session_logs',
        'fitness_enrollments',
        'fitness_orders',
        'fitness_program_files',
        'fitness_program_sessions',
        'fitness_program_phases',
        'fitness_program_access',
        'fitness_programs',
        'fitness_workout_exercises',
        'fitness_workouts',
        'fitness_exercise_files',
        'fitness_exercise_muscles',
        'fitness_files',
        'fitness_exercises',
        'fitness_muscle_groups',
        'fitness_categories',
    ];

    public function install(): void
    {
        $this->db()->queryMulti($this->getInstallSql());

        $databaseConfig = new Database($this->db());
        $databaseConfig->set('fitness_ownLayout', '1');

        (new MilestoneMapper())->createDefaults();
    }

    public function uninstall(): void
    {
        foreach (self::TABLES as $table) {
            $this->db()->drop($table, true);
        }

        $databaseConfig = new Database($this->db());
        $databaseConfig->delete('fitness_ownLayout');
    }

    /**
     * Returns the names of all tables of this module without prefix.
     *
     * @return string[]
     */
    public function getTables(): array
    {
        return self::TABLES;
    }

    /**
     * Registers the boxes of this module that are still missing. The module manager only
     * does this on installation, so updates that bring a new box call this method.
     */
    private function installBoxes(): void
    {
        $boxMapper = new BoxMapper();
        $boxModel = (new BoxModel())->setModule($this->config['key']);

        foreach ($this->config['boxes'] as $key => $names) {
            if (!$boxMapper->modulesBoxExists($key, $this->config['key'])) {
                $boxModel->addContent($key, $names);
            }
        }

        if ($boxModel->getContent()) {
            $boxMapper->install($boxModel);
        }
    }

    public function getInstallSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS `[prefix]_fitness_categories` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `position` INT(11) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_muscle_groups` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `position` INT(11) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_exercises` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `category_id` INT(11) NULL DEFAULT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `description` MEDIUMTEXT NOT NULL,
                    `instructions` MEDIUMTEXT NOT NULL,
                    `notes` TEXT NOT NULL,
                    `difficulty` TINYINT(1) NOT NULL DEFAULT 1,
                    `image` VARCHAR(255) NOT NULL DEFAULT \'\',
                    `video_url` VARCHAR(255) NOT NULL DEFAULT \'\',
                    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
                    `active` TINYINT(1) NOT NULL DEFAULT 1,
                    `position` INT(11) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    INDEX `FK_[prefix]_fit_ex_cat` (`category_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_ex_cat` FOREIGN KEY (`category_id`) REFERENCES `[prefix]_fitness_categories` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_exercise_muscles` (
                    `exercise_id` INT(11) NOT NULL,
                    `muscle_group_id` INT(11) NOT NULL,
                    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`exercise_id`, `muscle_group_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_exm_mg` (`muscle_group_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_exm_ex` FOREIGN KEY (`exercise_id`) REFERENCES `[prefix]_fitness_exercises` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_exm_mg` FOREIGN KEY (`muscle_group_id`) REFERENCES `[prefix]_fitness_muscle_groups` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_files` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(255) NOT NULL,
                    `stored_name` VARCHAR(100) NOT NULL,
                    `original_name` VARCHAR(255) NOT NULL,
                    `mime` VARCHAR(100) NOT NULL,
                    `size` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `stored_name` (`stored_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_exercise_files` (
                    `exercise_id` INT(11) NOT NULL,
                    `file_id` INT(11) NOT NULL,
                    PRIMARY KEY (`exercise_id`, `file_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_exf_file` (`file_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_exf_ex` FOREIGN KEY (`exercise_id`) REFERENCES `[prefix]_fitness_exercises` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_exf_file` FOREIGN KEY (`file_id`) REFERENCES `[prefix]_fitness_files` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_workouts` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(255) NOT NULL,
                    `description` MEDIUMTEXT NOT NULL,
                    `duration_min` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
                    `difficulty` TINYINT(1) NOT NULL DEFAULT 1,
                    `active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_workout_exercises` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `workout_id` INT(11) NOT NULL,
                    `exercise_id` INT(11) NOT NULL,
                    `position` INT(11) NOT NULL DEFAULT 0,
                    `sets` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
                    `reps_min` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
                    `reps_max` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
                    `weight` VARCHAR(50) NOT NULL DEFAULT \'\',
                    `duration_sec` INT(11) UNSIGNED NULL DEFAULT NULL,
                    `rest_sec` INT(11) UNSIGNED NULL DEFAULT NULL,
                    `notes` TEXT NOT NULL,
                    PRIMARY KEY (`id`),
                    INDEX `FK_[prefix]_fit_wex_wo` (`workout_id`, `position`) USING BTREE,
                    INDEX `FK_[prefix]_fit_wex_ex` (`exercise_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_wex_wo` FOREIGN KEY (`workout_id`) REFERENCES `[prefix]_fitness_workouts` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_wex_ex` FOREIGN KEY (`exercise_id`) REFERENCES `[prefix]_fitness_exercises` (`id`) ON UPDATE NO ACTION ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_programs` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(255) NOT NULL,
                    `teaser` VARCHAR(500) NOT NULL DEFAULT \'\',
                    `description` MEDIUMTEXT NOT NULL,
                    `image` VARCHAR(255) NOT NULL DEFAULT \'\',
                    `goal` VARCHAR(255) NOT NULL DEFAULT \'\',
                    `difficulty` TINYINT(1) NOT NULL DEFAULT 1,
                    `type` VARCHAR(30) NOT NULL DEFAULT \'weekly\',
                    `access_type` TINYINT(1) NOT NULL DEFAULT 0,
                    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `currency` CHAR(3) NOT NULL DEFAULT \'EUR\',
                    `status` TINYINT(1) NOT NULL DEFAULT 0,
                    `read_access_all` TINYINT(1) NOT NULL DEFAULT 1,
                    `position` INT(11) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_program_access` (
                    `program_id` INT(11) NOT NULL,
                    `group_id` INT(11) NOT NULL,
                    PRIMARY KEY (`program_id`, `group_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_pacc_grp` (`group_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_pacc_prog` FOREIGN KEY (`program_id`) REFERENCES `[prefix]_fitness_programs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_pacc_grp` FOREIGN KEY (`group_id`) REFERENCES `[prefix]_groups` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_program_phases` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `program_id` INT(11) NOT NULL,
                    `position` INT(11) NOT NULL DEFAULT 0,
                    `title` VARCHAR(255) NOT NULL,
                    `description` TEXT NOT NULL,
                    PRIMARY KEY (`id`),
                    INDEX `FK_[prefix]_fit_pph_prog` (`program_id`, `position`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_pph_prog` FOREIGN KEY (`program_id`) REFERENCES `[prefix]_fitness_programs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_program_sessions` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `program_id` INT(11) NOT NULL,
                    `phase_id` INT(11) NOT NULL,
                    `workout_id` INT(11) NOT NULL,
                    `position` INT(11) NOT NULL DEFAULT 0,
                    `title` VARCHAR(255) NOT NULL,
                    `day_hint` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
                    `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    INDEX `FK_[prefix]_fit_pses_prog` (`program_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_pses_phase` (`phase_id`, `position`) USING BTREE,
                    INDEX `FK_[prefix]_fit_pses_wo` (`workout_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_pses_prog` FOREIGN KEY (`program_id`) REFERENCES `[prefix]_fitness_programs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_pses_phase` FOREIGN KEY (`phase_id`) REFERENCES `[prefix]_fitness_program_phases` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_pses_wo` FOREIGN KEY (`workout_id`) REFERENCES `[prefix]_fitness_workouts` (`id`) ON UPDATE NO ACTION ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_program_files` (
                    `program_id` INT(11) NOT NULL,
                    `file_id` INT(11) NOT NULL,
                    PRIMARY KEY (`program_id`, `file_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_pf_file` (`file_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_pf_prog` FOREIGN KEY (`program_id`) REFERENCES `[prefix]_fitness_programs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_pf_file` FOREIGN KEY (`file_id`) REFERENCES `[prefix]_fitness_files` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_orders` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `user_id` INT(11) UNSIGNED NULL DEFAULT NULL,
                    `program_id` INT(11) NOT NULL,
                    `amount` DECIMAL(10,2) NOT NULL,
                    `currency` CHAR(3) NOT NULL,
                    `status` TINYINT(1) NOT NULL DEFAULT 0,
                    `payment_method` VARCHAR(20) NOT NULL DEFAULT \'\',
                    `reference_code` VARCHAR(32) NOT NULL,
                    `provider_order_id` VARCHAR(64) NULL DEFAULT NULL,
                    `provider_capture_id` VARCHAR(64) NULL DEFAULT NULL,
                    `created_at` DATETIME NOT NULL,
                    `paid_at` DATETIME NULL DEFAULT NULL,
                    `confirmed_by` INT(11) UNSIGNED NULL DEFAULT NULL,
                    `note` TEXT NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `reference_code` (`reference_code`),
                    UNIQUE INDEX `provider_order_id` (`provider_order_id`),
                    INDEX `FK_[prefix]_fit_ord_user` (`user_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_ord_prog` (`program_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_ord_conf` (`confirmed_by`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_ord_user` FOREIGN KEY (`user_id`) REFERENCES `[prefix]_users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
                    CONSTRAINT `FK_[prefix]_fit_ord_prog` FOREIGN KEY (`program_id`) REFERENCES `[prefix]_fitness_programs` (`id`) ON UPDATE NO ACTION ON DELETE RESTRICT,
                    CONSTRAINT `FK_[prefix]_fit_ord_conf` FOREIGN KEY (`confirmed_by`) REFERENCES `[prefix]_users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_enrollments` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `program_id` INT(11) NOT NULL,
                    `user_id` INT(11) UNSIGNED NOT NULL,
                    `status` TINYINT(1) NOT NULL DEFAULT 1,
                    `source` TINYINT(1) NOT NULL DEFAULT 0,
                    `order_id` INT(11) NULL DEFAULT NULL,
                    `started_at` DATETIME NOT NULL,
                    `completed_at` DATETIME NULL DEFAULT NULL,
                    `access_until` DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `program_user` (`program_id`, `user_id`),
                    INDEX `FK_[prefix]_fit_enr_user` (`user_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_enr_ord` (`order_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_enr_prog` FOREIGN KEY (`program_id`) REFERENCES `[prefix]_fitness_programs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_enr_user` FOREIGN KEY (`user_id`) REFERENCES `[prefix]_users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_enr_ord` FOREIGN KEY (`order_id`) REFERENCES `[prefix]_fitness_orders` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_session_logs` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `enrollment_id` INT(11) NOT NULL,
                    `program_session_id` INT(11) NOT NULL,
                    `completed_at` DATETIME NOT NULL,
                    `duration_sec` INT(11) UNSIGNED NULL DEFAULT NULL,
                    `rating` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
                    `note` TEXT NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `enrollment_session` (`enrollment_id`, `program_session_id`),
                    INDEX `FK_[prefix]_fit_slog_ses` (`program_session_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_slog_enr` FOREIGN KEY (`enrollment_id`) REFERENCES `[prefix]_fitness_enrollments` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_slog_ses` FOREIGN KEY (`program_session_id`) REFERENCES `[prefix]_fitness_program_sessions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_milestones` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `program_id` INT(11) NULL DEFAULT NULL,
                    `phase_id` INT(11) NULL DEFAULT NULL,
                    `type` VARCHAR(50) NOT NULL,
                    `threshold` INT(11) NULL DEFAULT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `description` TEXT NOT NULL,
                    `icon` VARCHAR(100) NOT NULL DEFAULT \'\',
                    `position` INT(11) NOT NULL DEFAULT 0,
                    `active` TINYINT(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`),
                    INDEX `FK_[prefix]_fit_ms_prog` (`program_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_ms_phase` (`phase_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_ms_prog` FOREIGN KEY (`program_id`) REFERENCES `[prefix]_fitness_programs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_ms_phase` FOREIGN KEY (`phase_id`) REFERENCES `[prefix]_fitness_program_phases` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

                CREATE TABLE IF NOT EXISTS `[prefix]_fitness_user_milestones` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `milestone_id` INT(11) NOT NULL,
                    `user_id` INT(11) UNSIGNED NOT NULL,
                    `enrollment_id` INT(11) NULL DEFAULT NULL,
                    `achieved_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `milestone_user` (`milestone_id`, `user_id`),
                    INDEX `FK_[prefix]_fit_ums_user` (`user_id`) USING BTREE,
                    INDEX `FK_[prefix]_fit_ums_enr` (`enrollment_id`) USING BTREE,
                    CONSTRAINT `FK_[prefix]_fit_ums_ms` FOREIGN KEY (`milestone_id`) REFERENCES `[prefix]_fitness_milestones` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_ums_user` FOREIGN KEY (`user_id`) REFERENCES `[prefix]_users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
                    CONSTRAINT `FK_[prefix]_fit_ums_enr` FOREIGN KEY (`enrollment_id`) REFERENCES `[prefix]_fitness_enrollments` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;';
    }

    public function getUpdate(string $installedVersion): string
    {
        switch ($installedVersion) {
            case '1.0.0':
                // Milestones and the progress box came with 1.1.0.
                (new MilestoneMapper())->createDefaults();
                $this->installBoxes();
        }

        return '"' . $this->config['key'] . '" Update-function executed.';
    }
}
