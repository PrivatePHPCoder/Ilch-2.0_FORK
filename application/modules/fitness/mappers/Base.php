<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Mapper;

/**
 * Common base for the mappers of the fitness module.
 */
abstract class Base extends Mapper
{
    /**
     * Updates the row with the given id.
     *
     * The query builder skips null values. This method writes them as NULL, so a value
     * can be removed again (for example the category of an exercise).
     *
     * @param string $table table without prefix
     * @param int $id
     * @param array $fields column => value
     */
    protected function updateRow(string $table, int $id, array $fields): void
    {
        $values = array_filter($fields, static fn ($value) => $value !== null);
        $nullColumns = array_keys(array_diff_key($fields, $values));

        if ($values) {
            $this->db()->update($table)
                ->values($values)
                ->where(['id' => $id])
                ->execute();
        }

        if ($nullColumns) {
            $set = implode(', ', array_map(fn ($column) => $this->db()->quote($column) . ' = NULL', $nullColumns));
            $this->db()->query('UPDATE ' . $this->db()->quote('[prefix]_' . $table) . ' SET ' . $set . ' WHERE `id` = ' . $id);
        }
    }

    /**
     * Saves the order of rows.
     *
     * @param string $table table without prefix
     * @param int[] $ids ids in the new order
     */
    protected function updatePositionsOf(string $table, array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            $this->db()->update($table)
                ->values(['position' => $position])
                ->where(['id' => (int)$id])
                ->execute();
        }
    }

    /**
     * Returns the next free position at the end of a table.
     *
     * @param string $table table without prefix
     * @param array $where restricts the rows, for example to one workout
     * @return int
     */
    protected function getNextPositionOf(string $table, array $where = []): int
    {
        return (int)$this->db()->select('MAX(position)')
            ->from($table)
            ->where($where)
            ->execute()
            ->fetchCell() + 1;
    }
}
