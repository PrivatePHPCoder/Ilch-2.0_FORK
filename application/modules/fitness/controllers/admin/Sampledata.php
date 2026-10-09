<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Modules\Fitness\Service\SampleData as SampleDataService;

/**
 * Adds and removes the sample content. Both actions are only reachable by POST and lead back to
 * the overview, where the buttons are.
 */
class Sampledata extends Base
{
    public function installAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->redirect(['controller' => 'index', 'action' => 'index']);
        }

        $counts = (new SampleDataService($this->getConfig()))->install($this->getTranslator()->getLocale());

        $this->redirect()
            ->withMessage($counts === null ? 'sampleDataExists' : 'sampleDataInstalled', $counts === null ? 'info' : 'success')
            ->to(['controller' => 'index', 'action' => 'index']);
    }

    public function removeAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->redirect(['controller' => 'index', 'action' => 'index']);
        }

        $result = (new SampleDataService($this->getConfig()))->remove();

        $this->redirect()
            ->withMessage($result['kept'] ? 'sampleDataPartlyRemoved' : 'sampleDataRemoved', $result['kept'] ? 'warning' : 'success')
            ->to(['controller' => 'index', 'action' => 'index']);
    }
}
