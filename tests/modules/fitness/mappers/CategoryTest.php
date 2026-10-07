<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Category as CategoryModel;
use Modules\Fitness\Models\Exercise as ExerciseModel;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

class CategoryTest extends DatabaseTestCase
{
    protected Category $out;

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Category();
    }

    public function testSaveAndGetCategory()
    {
        $id = $this->out->save((new CategoryModel())->setName('Kraft'));

        $category = $this->out->getCategoryById($id);
        self::assertNotNull($category);
        self::assertSame('Kraft', $category->getName());
        self::assertSame(0, $category->getExerciseCount());
    }

    public function testGetCategoryByIdReturnsNullForUnknownId()
    {
        self::assertNull($this->out->getCategoryById(4711));
    }

    public function testNewCategoriesAreAddedAtTheEnd()
    {
        $this->out->save((new CategoryModel())->setName('B'));
        $this->out->save((new CategoryModel())->setName('A'));

        $names = array_map(static fn ($category) => $category->getName(), $this->out->getCategories());
        self::assertSame(['B', 'A'], $names);
    }

    public function testUpdatePositionsChangesOrder()
    {
        $first = $this->out->save((new CategoryModel())->setName('Erste'));
        $second = $this->out->save((new CategoryModel())->setName('Zweite'));

        $this->out->updatePositions([$second, $first]);

        $names = array_map(static fn ($category) => $category->getName(), $this->out->getCategories());
        self::assertSame(['Zweite', 'Erste'], $names);
    }

    public function testExerciseCount()
    {
        $id = $this->out->save((new CategoryModel())->setName('Kraft'));
        $exerciseMapper = new Exercise();
        $exerciseMapper->save((new ExerciseModel())->setTitle('Kniebeuge')->setCategoryId($id));
        $exerciseMapper->save((new ExerciseModel())->setTitle('Kreuzheben')->setCategoryId($id));

        self::assertSame(2, $this->out->getCategoryById($id)->getExerciseCount());
    }

    public function testDeleteKeepsExercises()
    {
        $id = $this->out->save((new CategoryModel())->setName('Kraft'));
        $exerciseMapper = new Exercise();
        $exerciseId = $exerciseMapper->save((new ExerciseModel())->setTitle('Kniebeuge')->setCategoryId($id));

        self::assertTrue($this->out->delete($id));

        $exercise = $exerciseMapper->getExerciseById($exerciseId);
        self::assertNotNull($exercise);
        self::assertNull($exercise->getCategoryId());
    }

    /**
     * Returns database schema sql statements to initialize database
     *
     * @return string
     */
    protected static function getSchemaSQLQueries(): string
    {
        $config = new ModuleConfig();
        $userConfig = new UserConfig();
        $adminConfig = new AdminConfig();

        return $adminConfig->getInstallSql() . $userConfig->getInstallSql() . $config->getInstallSql();
    }
}
