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
     *
     * @var array<string, array{name: string, icon: string}>
     */
    private const MENU = [
        'index' => ['name' => 'menuOverview', 'icon' => 'fa-solid fa-gauge'],
        'settings' => ['name' => 'menuSettings', 'icon' => 'fa-solid fa-gears'],
    ];

    public function init()
    {
        $items = [];
        foreach (self::MENU as $controller => $entry) {
            $items[] = [
                'name' => $entry['name'],
                'active' => $this->getRequest()->getControllerName() === $controller,
                'icon' => $entry['icon'],
                'url' => $this->getLayout()->getUrl(['controller' => $controller, 'action' => 'index']),
            ];
        }

        $this->getLayout()->addMenu('menuFitness', $items);
    }
}
