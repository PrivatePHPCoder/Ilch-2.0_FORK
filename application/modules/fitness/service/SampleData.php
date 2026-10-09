<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Ilch\Config\Database as DatabaseConfig;
use Modules\Fitness\Mappers\Category as CategoryMapper;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Exercise as ExerciseMapper;
use Modules\Fitness\Mappers\MuscleGroup as MuscleGroupMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Mappers\ProgramStructure as ProgramStructureMapper;
use Modules\Fitness\Mappers\Workout as WorkoutMapper;
use Modules\Fitness\Models\Category as CategoryModel;
use Modules\Fitness\Models\Exercise as ExerciseModel;
use Modules\Fitness\Models\MuscleGroup as MuscleGroupModel;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\Fitness\Models\ProgramPhase as ProgramPhaseModel;
use Modules\Fitness\Models\ProgramSession as ProgramSessionModel;
use Modules\Fitness\Models\Workout as WorkoutModel;
use Modules\Fitness\Models\WorkoutExercise as WorkoutExerciseModel;

/**
 * Adds complete sample content (categories, muscle groups, exercises, workouts and programs) and
 * removes it again. The ids of the added entries are kept in the setting fitness_sampleData,
 * so removing never touches own entries.
 */
class SampleData
{
    public const CONFIG_KEY = 'fitness_sampleData';

    /**
     * Kinds of entries, in the order they are removed (users of an entry before the entry).
     *
     * @var string[]
     */
    public const TYPES = ['programs', 'workouts', 'exercises', 'categories', 'muscleGroups'];

    /**
     * Path of the sample images, relative to the base URL like images of the media library.
     */
    private const IMAGE_PATH = 'application/modules/fitness/static/sample/';

    /**
     * @var DatabaseConfig
     */
    private DatabaseConfig $config;

    /**
     * Sample content, loaded from config/sampledata.php when needed.
     *
     * @var array|null
     */
    private ?array $data;

    /**
     * @param DatabaseConfig $config
     * @param array|null $data sample content, by default config/sampledata.php
     */
    public function __construct(DatabaseConfig $config, ?array $data = null)
    {
        $this->config = $config;
        $this->data = $data;
    }

    /**
     * Returns the ids of the sample entries that were added.
     *
     * @return array<string, int[]> kind => ids
     */
    public function getIds(): array
    {
        $stored = json_decode((string)$this->config->get(self::CONFIG_KEY, true), true);

        $ids = [];
        foreach (self::TYPES as $type) {
            $ids[$type] = array_values(array_map('intval', (array)($stored[$type] ?? [])));
        }

        return $ids;
    }

    public function isInstalled(): bool
    {
        return (bool)array_filter($this->getIds());
    }

    /**
     * Adds the sample content in the given language. Categories and muscle groups with the same
     * name are used instead of adding a second one.
     *
     * @param string $locale for example "de_DE"; German for "de_*", English otherwise
     * @return array<string, int>|null number of added entries per kind, null if sample data exists already
     */
    public function install(string $locale): ?array
    {
        if ($this->isInstalled()) {
            return null;
        }

        $this->data = $this->data ?? require dirname(__DIR__) . '/config/sampledata.php';
        $language = strncmp($locale, 'de', 2) === 0 ? 'de' : 'en';
        $ids = array_fill_keys(self::TYPES, []);

        // Store what was added so far even if something fails, so it can still be removed.
        try {
            $categoryIds = $this->installNamedEntries('categories', new CategoryMapper(), CategoryModel::class, $language, $ids);
            $muscleGroupIds = $this->installNamedEntries('muscleGroups', new MuscleGroupMapper(), MuscleGroupModel::class, $language, $ids);
            $exerciseIds = $this->installExercises($language, $categoryIds, $muscleGroupIds, $ids);
            $workoutIds = $this->installWorkouts($language, $exerciseIds, $ids);
            $this->installPrograms($language, $workoutIds, $ids);
        } finally {
            $this->storeIds($ids);
        }

        return array_map('count', $ids);
    }

    /**
     * Removes the sample entries. Entries that own entries still use are kept: exercises in own
     * workouts, workouts in own programs, categories and muscle groups of own exercises, and
     * programs with orders. Participations in sample programs are removed with the program.
     *
     * @return array{deleted: int, kept: int}
     */
    public function remove(): array
    {
        $ids = $this->getIds();
        $kept = array_fill_keys(self::TYPES, []);
        $deleted = 0;

        $programMapper = new ProgramMapper();
        $workoutMapper = new WorkoutMapper();
        $exerciseMapper = new ExerciseMapper();
        $categoryMapper = new CategoryMapper();
        $muscleGroupMapper = new MuscleGroupMapper();

        // For every kind: does the entry still exist, and how is it deleted (false = kept).
        $handlers = [
            'programs' => [
                static fn (int $id) => $programMapper->getProgramById($id) !== null,
                static fn (int $id) => $programMapper->deleteWithParticipants($id),
            ],
            'workouts' => [
                static fn (int $id) => $workoutMapper->getWorkoutById($id, false) !== null,
                static fn (int $id) => $workoutMapper->delete($id),
            ],
            'exercises' => [
                static fn (int $id) => $exerciseMapper->getExerciseById($id) !== null,
                static fn (int $id) => $exerciseMapper->delete($id),
            ],
            'categories' => [
                static fn (int $id) => $categoryMapper->getCategoryById($id) !== null,
                static fn (int $id) => $categoryMapper->getCategoryById($id)->getExerciseCount() === 0 && $categoryMapper->delete($id),
            ],
            'muscleGroups' => [
                static fn (int $id) => $muscleGroupMapper->getMuscleGroupById($id) !== null,
                static fn (int $id) => $muscleGroupMapper->getMuscleGroupById($id)->getExerciseCount() === 0 && $muscleGroupMapper->delete($id),
            ],
        ];

        foreach (self::TYPES as $type) {
            [$exists, $delete] = $handlers[$type];
            foreach ($ids[$type] as $id) {
                if (!$exists($id)) {
                    continue;
                }

                if ($delete($id)) {
                    $deleted++;
                } else {
                    $kept[$type][] = $id;
                }
            }
        }

        $this->storeIds($kept);

        return ['deleted' => $deleted, 'kept' => (int)array_sum(array_map('count', $kept))];
    }

    /**
     * Returns how many participations the sample programs have. They are removed together with
     * the programs.
     *
     * @return int
     */
    public function getParticipantCount(): int
    {
        $counts = (new EnrollmentMapper())->getCountsPerProgram();

        return (int)array_sum(array_intersect_key($counts, array_flip($this->getIds()['programs'])));
    }

    /**
     * Adds categories or muscle groups. An existing entry with the same name is used instead.
     *
     * @param string $type 'categories' or 'muscleGroups'
     * @param CategoryMapper|MuscleGroupMapper $mapper
     * @param string $modelClass
     * @param string $language
     * @param array $ids ids of added entries, gets extended
     * @return array<string, int> sample key => id
     */
    private function installNamedEntries(string $type, $mapper, string $modelClass, string $language, array &$ids): array
    {
        $existing = [];
        $entries = $type === 'categories' ? $mapper->getCategories() : $mapper->getMuscleGroups();
        foreach ($entries as $entry) {
            $existing[mb_strtolower($entry->getName())] = $entry->getId();
        }

        $result = [];
        foreach ($this->data[$type] as $key => $names) {
            $name = $names[$language];
            if (isset($existing[mb_strtolower($name)])) {
                $result[$key] = $existing[mb_strtolower($name)];
                continue;
            }

            $id = $mapper->save((new $modelClass())->setName($name));
            $ids[$type][] = $id;
            $result[$key] = $id;
        }

        return $result;
    }

    /**
     * @param string $language
     * @param array<string, int> $categoryIds
     * @param array<string, int> $muscleGroupIds
     * @param array $ids
     * @return array<string, int> sample key => id
     */
    private function installExercises(string $language, array $categoryIds, array $muscleGroupIds, array &$ids): array
    {
        $mapper = new ExerciseMapper();
        $result = [];

        foreach ($this->data['exercises'] as $key => $exercise) {
            $id = $mapper->save((new ExerciseModel())
                ->setTitle($exercise['title'][$language])
                ->setCategoryId($categoryIds[$exercise['category']] ?? null)
                ->setDifficulty($exercise['difficulty'])
                ->setDescription($exercise['description'][$language])
                ->setInstructions($exercise['instructions'][$language])
                ->setNotes($exercise['notes'][$language])
                ->setImage(self::IMAGE_PATH . $key . '.svg')
                ->setVideoUrl($exercise['video'])
                ->setPublic(true)
                ->setActive(true)
                ->setMuscleGroups(
                    array_map(static fn ($muscle) => $muscleGroupIds[$muscle], $exercise['muscles']),
                    $muscleGroupIds[$exercise['primary']]
                ));

            $ids['exercises'][] = $id;
            $result[$key] = $id;
        }

        return $result;
    }

    /**
     * @param string $language
     * @param array<string, int> $exerciseIds
     * @param array $ids
     * @return array<string, int> sample key => id
     */
    private function installWorkouts(string $language, array $exerciseIds, array &$ids): array
    {
        $mapper = new WorkoutMapper();
        $result = [];

        foreach ($this->data['workouts'] as $key => $workout) {
            $items = [];
            foreach ($workout['items'] as [$exercise, $sets, $repsMin, $repsMax, $weight, $duration, $rest]) {
                $items[] = (new WorkoutExerciseModel())
                    ->setExerciseId($exerciseIds[$exercise])
                    ->setSets($sets)
                    ->setRepsMin($repsMin)
                    ->setRepsMax($repsMax)
                    ->setWeight(is_array($weight) ? $weight[$language] : $weight)
                    ->setDurationSec($duration)
                    ->setRestSec($rest);
            }

            $id = $mapper->save((new WorkoutModel())
                ->setTitle($workout['title'][$language])
                ->setDescription($workout['description'][$language])
                ->setDurationMin($workout['duration'])
                ->setDifficulty($workout['difficulty'])
                ->setActive(true)
                ->setExercises($items));

            $ids['workouts'][] = $id;
            $result[$key] = $id;
        }

        return $result;
    }

    /**
     * @param string $language
     * @param array<string, int> $workoutIds
     * @param array $ids
     */
    private function installPrograms(string $language, array $workoutIds, array &$ids): void
    {
        $programMapper = new ProgramMapper();
        $structureMapper = new ProgramStructureMapper();

        foreach ($this->data['programs'] as $program) {
            $id = $programMapper->save((new ProgramModel())
                ->setTitle($program['title'][$language])
                ->setTeaser($program['teaser'][$language])
                ->setDescription($program['description'][$language])
                ->setGoal($program['goal'][$language])
                ->setDifficulty($program['difficulty'])
                ->setImage(self::IMAGE_PATH . $program['image'] . '.svg')
                ->setStatus(ProgramModel::STATUS_PUBLISHED)
                ->setAccessType($program['price'] !== null ? ProgramModel::ACCESS_PAID : ProgramModel::ACCESS_FREE)
                ->setPrice($program['price'] ?? '0')
                ->setCurrency('EUR')
                ->setReadAccessAll(true));
            $ids['programs'][] = $id;

            $phases = [];
            foreach ($program['phases'] as $phase) {
                $sessions = [];
                foreach ($phase['sessions'] as $session) {
                    $sessions[] = (new ProgramSessionModel())
                        ->setWorkoutId($workoutIds[$session['workout']])
                        ->setTitle($session['title'][$language])
                        ->setDayHint($session['day'])
                        ->setOptional($session['optional']);
                }

                $phases[] = (new ProgramPhaseModel())
                    ->setTitle($phase['title'][$language])
                    ->setDescription($phase['description'][$language])
                    ->setSessions($sessions);
            }

            $structureMapper->syncStructure($id, $phases);
        }
    }

    /**
     * @param array<string, int[]> $ids
     */
    private function storeIds(array $ids): void
    {
        if (array_filter($ids)) {
            $this->config->set(self::CONFIG_KEY, json_encode($ids));
        } else {
            $this->config->delete(self::CONFIG_KEY);
        }
    }
}
