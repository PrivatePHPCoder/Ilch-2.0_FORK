<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Service\Access;

/**
 * Lets managers switch the admin preview off, to see the fitness area like a visitor.
 */
class Preview extends Base
{
    /**
     * Pages of the fitness area the switch may lead back to: controller => actions.
     *
     * @var array<string, string[]>
     */
    private const RETURN_TARGETS = [
        'index' => ['index'],
        'programs' => ['index', 'show'],
        'exercises' => ['index', 'show'],
        'training' => ['index', 'session'],
        'milestones' => ['index'],
        'orders' => ['index', 'show'],
    ];

    /**
     * Switches between visitor view and admin preview. Only reachable by POST. Leads back to the
     * page the switch was made on.
     */
    public function indexAction()
    {
        $controller = (string)$this->getRequest()->getPost('returnController');
        $action = (string)$this->getRequest()->getPost('returnAction');
        $id = (int)$this->getRequest()->getPost('returnId');

        $target = ['controller' => 'index', 'action' => 'index'];
        if (in_array($action, self::RETURN_TARGETS[$controller] ?? [], true)) {
            $target = array_merge(['controller' => $controller, 'action' => $action], $id ? ['id' => $id] : []);
        }

        if (!$this->getRequest()->isPost() || !Access::canManage($this->getUser())) {
            $this->redirect($target);
        }

        $visitorView = $this->getRequest()->getPost('mode') === 'visitor';
        $this->setVisitorView($visitorView);

        // Switched on, the visitor bar on every page tells it already.
        if ($visitorView) {
            $this->redirect($target);
        }

        $this->redirect()
            ->withMessage('visitorViewOff', 'info')
            ->to($target);
    }
}
