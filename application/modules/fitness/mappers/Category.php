<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Modules\Fitness\Models\Category as CategoryModel;

class Category extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_categories';

    /**
     * Returns categories with the number of their exercises.
     *
     * @param array $where
     * @param array $orderBy
     * @return CategoryModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['c.position' => 'ASC', 'c.name' => 'ASC']): array
    {
        $rows = $this->db()->select(['c.id', 'c.name', 'c.position'])
            ->from(['c' => $this->tablename])
            ->join(['e' => 'fitness_exercises'], 'e.category_id = c.id', 'LEFT', ['exercise_count' => 'COUNT(e.id)'])
            ->where($where)
            ->group(['c.id', 'c.name', 'c.position'])
            ->order($orderBy)
            ->execute()
            ->fetchRows();

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = (new CategoryModel())->setByArray($row);
        }

        return $categories;
    }

    /**
     * @return CategoryModel[]
     */
    public function getCategories(): array
    {
        return $this->getEntriesBy();
    }

    public function getCategoryById(int $id): ?CategoryModel
    {
        $categories = $this->getEntriesBy(['c.id' => $id]);

        return reset($categories) ?: null;
    }

    /**
     * Inserts or updates a category. New categories are put at the end of the list.
     *
     * @param CategoryModel $category
     * @return int id of the category
     */
    public function save(CategoryModel $category): int
    {
        if ($category->getId()) {
            $this->db()->update($this->tablename)
                ->values($category->getArray(false))
                ->where(['id' => $category->getId()])
                ->execute();

            return $category->getId();
        }

        $category->setPosition($this->getNextPositionOf($this->tablename));

        return (int)$this->db()->insert($this->tablename)
            ->values($category->getArray(false))
            ->execute();
    }

    /**
     * Saves the order of the categories.
     *
     * @param int[] $ids category ids in the new order
     */
    public function updatePositions(array $ids): void
    {
        $this->updatePositionsOf($this->tablename, $ids);
    }

    /**
     * Deletes a category. Its exercises stay and lose their category.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return (bool)$this->db()->delete($this->tablename)
            ->where(['id' => $id])
            ->execute();
    }
}
