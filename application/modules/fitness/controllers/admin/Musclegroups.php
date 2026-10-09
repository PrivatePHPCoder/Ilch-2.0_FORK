<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Modules\Fitness\Mappers\MuscleGroup as MuscleGroupMapper;
use Modules\Fitness\Models\MuscleGroup as MuscleGroupModel;

class Musclegroups extends Base
{
    public function indexAction()
    {
        $muscleGroupMapper = new MuscleGroupMapper();

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuMuscleGroups'), ['action' => 'index']);

        if ($this->getRequest()->getPost('action') === 'delete' && $this->getRequest()->getPost('check_musclegroups')) {
            foreach ($this->getRequest()->getPost('check_musclegroups') as $id) {
                $muscleGroupMapper->delete((int)$id);
            }

            $this->redirect()
                ->withMessage('deleteSuccess')
                ->to(['action' => 'index']);
        }

        if ($this->getRequest()->getPost('saveOrder')) {
            $muscleGroupMapper->updatePositions((array)$this->getRequest()->getPost('items'));

            $this->redirect()
                ->withMessage('saveSuccess')
                ->to(['action' => 'index']);
        }

        $this->getView()->set('muscleGroups', $muscleGroupMapper->getMuscleGroups())
            ->set('sampleIds', $this->getSampleIds('muscleGroups'));
    }

    public function treatAction()
    {
        $muscleGroupMapper = new MuscleGroupMapper();
        $muscleGroup = new MuscleGroupModel();

        if ($this->getRequest()->getParam('id')) {
            $muscleGroup = $muscleGroupMapper->getMuscleGroupById((int)$this->getRequest()->getParam('id'));

            if (!$muscleGroup) {
                $this->redirect()
                    ->withMessage('entryNotFound', 'danger')
                    ->to(['action' => 'index']);
            }
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuMuscleGroups'), ['action' => 'index'])
            ->add($this->getTranslator()->trans($muscleGroup->getId() ? 'edit' : 'add'), array_merge(['action' => 'treat'], $muscleGroup->getId() ? ['id' => $muscleGroup->getId()] : []));

        if ($this->getRequest()->isPost()) {
            $validation = Validation::create($this->getRequest()->getPost(), [
                'name' => 'required|max:100,string',
            ]);

            if ($validation->isValid()) {
                $muscleGroup->setName(trim($this->getRequest()->getPost('name')));
                $muscleGroupMapper->save($muscleGroup);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'index']);
            }

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(array_merge(['action' => 'treat'], $muscleGroup->getId() ? ['id' => $muscleGroup->getId()] : []));
        }

        $this->getView()->set('muscleGroup', $muscleGroup);
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            $muscleGroupMapper = new MuscleGroupMapper();
            $muscleGroupMapper->delete((int)$this->getRequest()->getParam('id'));

            $this->addMessage('deleteSuccess');
        }

        $this->redirect(['action' => 'index']);
    }
}
