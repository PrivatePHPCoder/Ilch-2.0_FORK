<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Service\Milestones as MilestonesService;

/**
 * Milestones of the logged-in user: reached ones and the way to the open ones.
 */
class Milestones extends Base
{
    public function init()
    {
        parent::init();
        $this->requireLogin();
    }

    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($this->getTranslator()->trans('myMilestones'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('myMilestones'), ['action' => 'index']);

        $userId = $this->getUser()->getId();
        $overview = $this->getMilestoneOverview($userId, $this->getTrainingsOfUser($userId));

        $reached = [];
        $open = [];
        foreach ($overview['milestones'] as $milestone) {
            if (isset($overview['achievements'][$milestone->getId()])) {
                $reached[] = $milestone;
            } else {
                $open[] = [
                    'milestone' => $milestone,
                    'current' => min(MilestonesService::getCurrentValue($milestone, $overview['stats']), $milestone->getThreshold()),
                ];
            }
        }

        // Newest first, like the dates on the page.
        usort($reached, static fn ($a, $b) => strcmp($overview['achievements'][$b->getId()], $overview['achievements'][$a->getId()]));

        $this->getView()->set('reached', $reached)
            ->set('open', $open)
            ->set('achievements', $overview['achievements'])
            ->set('totals', $overview['totals'])
            ->set('milestoneCount', count($overview['milestones']));
    }
}
