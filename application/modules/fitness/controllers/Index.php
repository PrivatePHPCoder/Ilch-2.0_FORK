<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

class Index extends Base
{
    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['action' => 'index']);
    }
}
