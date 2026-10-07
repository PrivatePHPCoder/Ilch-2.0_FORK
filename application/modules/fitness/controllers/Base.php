<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Ilch\Controller\Frontend;

/**
 * Common base for all frontend controllers of the fitness module.
 *
 * Controllers that define their own init() have to call parent::init().
 */
class Base extends Frontend
{
    /**
     * Group id of guests.
     */
    private const GROUP_GUEST = 3;

    public function init()
    {
        $this->useFitnessLayout();

        $this->getLayout()->header()
            ->css('static/css/fitness.css')
            ->js('static/js/fitness.js');
    }

    /**
     * Shows the page in the layout file of this module, if that is turned on in the settings.
     *
     * Only the default file of the active layout gets replaced. A file chosen by the maintenance
     * mode or by a layout route of the active layout (for example
     * 'layouts' => ['fitness' => [['module' => 'fitness']]]) stays untouched.
     */
    protected function useFitnessLayout(): void
    {
        $layout = $this->getLayout();
        $layoutKey = $layout->getLayoutKey();

        if ($this->getConfig()->get('fitness_ownLayout') && $layout->getFile() === 'layouts/' . $layoutKey . '/index') {
            $layout->setFile('modules/fitness/layouts/fitness', $layoutKey);
        }
    }

    /**
     * Returns the group ids of the current visitor. Guests belong to the guest group.
     *
     * @return int[]
     */
    protected function getVisitorGroupIds(): array
    {
        $user = $this->getUser();

        return $user ? array_map('intval', array_keys($user->getGroups())) : [self::GROUP_GUEST];
    }

    /**
     * Returns whether the visitor may manage the fitness module. Such visitors may preview
     * unpublished programs and inactive exercises.
     *
     * @return bool
     */
    protected function canManageFitness(): bool
    {
        $user = $this->getUser();

        return $user && ($user->isAdmin() || $user->hasAccess('module_fitness'));
    }
}
