<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Controller\Admin;

/**
 * Common base for all admin controllers of the fitness module.
 *
 * Builds the side menu in one place. Controllers that define their own init()
 * have to call parent::init().
 */
class Base extends Admin
{
    /**
     * Entries of the side menu, keyed by controller name.
     * With 'add' => true the entry gets a sub entry for the treat action.
     *
     * @var array<string, array{name: string, icon: string, add?: bool}>
     */
    private const MENU = [
        'index' => ['name' => 'menuOverview', 'icon' => 'fa-solid fa-gauge'],
        'exercises' => ['name' => 'menuExercises', 'icon' => 'fa-solid fa-person-running', 'add' => true],
        'workouts' => ['name' => 'menuWorkouts', 'icon' => 'fa-solid fa-list-check', 'add' => true],
        'programs' => ['name' => 'menuPrograms', 'icon' => 'fa-solid fa-calendar-week', 'add' => true],
        'participants' => ['name' => 'menuParticipants', 'icon' => 'fa-solid fa-users'],
        'milestones' => ['name' => 'menuMilestones', 'icon' => 'fa-solid fa-medal', 'add' => true],
        'categories' => ['name' => 'menuCategories', 'icon' => 'fa-solid fa-tags', 'add' => true],
        'musclegroups' => ['name' => 'menuMuscleGroups', 'icon' => 'fa-solid fa-hand-fist', 'add' => true],
        'settings' => ['name' => 'menuSettings', 'icon' => 'fa-solid fa-gears'],
    ];

    public function init()
    {
        $currentController = $this->getRequest()->getControllerName();
        $isTreat = $this->getRequest()->getActionName() === 'treat';

        $items = [];
        foreach (self::MENU as $controller => $entry) {
            $item = [
                'name' => $entry['name'],
                'active' => $currentController === $controller && !($isTreat && !empty($entry['add'])),
                'icon' => $entry['icon'],
                'url' => $this->getLayout()->getUrl(['controller' => $controller, 'action' => 'index']),
            ];

            if (!empty($entry['add'])) {
                $item[] = [
                    'name' => 'add',
                    'active' => $currentController === $controller && $isTreat,
                    'icon' => 'fa-solid fa-circle-plus',
                    'url' => $this->getLayout()->getUrl(['controller' => $controller, 'action' => 'treat']),
                ];
            }

            $items[] = $item;
        }

        $this->getLayout()->addMenu('menuFitness', $items);
    }

    /**
     * Accepts an empty value, an http(s) URL or a relative path like the ones of the media library.
     *
     * @param string $image
     * @return bool
     */
    protected function isValidImage(string $image): bool
    {
        if ($image === '') {
            return true;
        }

        if (preg_match('~^https?://~i', $image)) {
            return filter_var($image, FILTER_VALIDATE_URL) !== false;
        }

        return preg_match('~^[A-Za-z0-9_\-./]+$~', $image) === 1 && strpos($image, '..') === false;
    }
}
