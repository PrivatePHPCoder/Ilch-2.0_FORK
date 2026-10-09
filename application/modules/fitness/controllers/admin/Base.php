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
     * Sections of the side menu with their entries, keyed by controller name. Adding new entries
     * happens with the button at the top of each list, so the menu has no "add" entries.
     *
     * @var array<string, array<string, array{name: string, icon: string}>>
     */
    private const MENU = [
        'menuFitness' => [
            'index' => ['name' => 'menuOverview', 'icon' => 'fa-solid fa-gauge'],
            'settings' => ['name' => 'menuSettings', 'icon' => 'fa-solid fa-gears'],
        ],
        'menuSectionTraining' => [
            'programs' => ['name' => 'menuPrograms', 'icon' => 'fa-solid fa-calendar-week'],
            'workouts' => ['name' => 'menuWorkouts', 'icon' => 'fa-solid fa-list-check'],
            'exercises' => ['name' => 'menuExercises', 'icon' => 'fa-solid fa-person-running'],
        ],
        'menuSectionParticipants' => [
            'participants' => ['name' => 'menuParticipants', 'icon' => 'fa-solid fa-users'],
            'orders' => ['name' => 'menuOrders', 'icon' => 'fa-solid fa-receipt'],
            'milestones' => ['name' => 'menuMilestones', 'icon' => 'fa-solid fa-medal'],
        ],
        'menuSectionMasterData' => [
            'categories' => ['name' => 'menuCategories', 'icon' => 'fa-solid fa-tags'],
            'musclegroups' => ['name' => 'menuMuscleGroups', 'icon' => 'fa-solid fa-hand-fist'],
        ],
    ];

    public function init()
    {
        $currentController = $this->getRequest()->getControllerName();

        foreach (self::MENU as $section => $entries) {
            $items = [];
            foreach ($entries as $controller => $entry) {
                $items[] = [
                    'name' => $entry['name'],
                    'active' => $currentController === $controller,
                    'icon' => $entry['icon'],
                    'url' => $this->getLayout()->getUrl(['controller' => $controller, 'action' => 'index']),
                ];
            }

            $this->getLayout()->addMenu($section, $items);
        }
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
