<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Modules\Fitness\Mappers\Milestone as MilestoneMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Models\Milestone as MilestoneModel;

class Milestones extends Base
{
    public function indexAction()
    {
        $milestoneMapper = new MilestoneMapper();

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuMilestones'), ['action' => 'index']);

        if ($this->getRequest()->getPost('action') === 'delete' && $this->getRequest()->getPost('check_milestones')) {
            foreach ($this->getRequest()->getPost('check_milestones') as $id) {
                $milestoneMapper->delete((int)$id);
            }

            $this->redirect()
                ->withMessage('deleteSuccess')
                ->to(['action' => 'index']);
        }

        if ($this->getRequest()->getPost('saveOrder')) {
            $milestoneMapper->updatePositions((array)$this->getRequest()->getPost('items'));

            $this->redirect()
                ->withMessage('saveSuccess')
                ->to(['action' => 'index']);
        }

        if ($this->getRequest()->getPost('createDefaults')) {
            $created = $milestoneMapper->createDefaults();

            $this->redirect()
                ->withMessage($created ? 'milestoneDefaultsCreated' : 'milestoneDefaultsExist', $created ? 'success' : 'info')
                ->to(['action' => 'index']);
        }

        $this->getView()->set('milestones', $milestoneMapper->getMilestones());
    }

    public function treatAction()
    {
        $milestoneMapper = new MilestoneMapper();
        $milestone = new MilestoneModel();

        if ($this->getRequest()->getParam('id')) {
            $milestone = $milestoneMapper->getMilestoneById((int)$this->getRequest()->getParam('id'));

            if (!$milestone) {
                $this->redirect()
                    ->withMessage('entryNotFound', 'danger')
                    ->to(['action' => 'index']);
            }
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuMilestones'), ['action' => 'index'])
            ->add($this->getTranslator()->trans($milestone->getId() ? 'edit' : 'add'), array_merge(['action' => 'treat'], $milestone->getId() ? ['id' => $milestone->getId()] : []));

        $programs = [];
        foreach ((new ProgramMapper())->getPrograms() as $program) {
            $programs[$program->getId()] = $program;
        }

        if ($this->getRequest()->isPost()) {
            $validation = Validation::create($this->getRequest()->getPost(), [
                'title' => 'max:255,string',
                'description' => 'max:1000,string',
                'type' => 'required',
                'threshold' => 'required|integer|min:1',
                'active' => 'required|integer|min:0|max:1',
            ]);

            $type = (string)$this->getRequest()->getPost('type');
            if (!isset(MilestoneModel::TYPES[$type])) {
                $validation->getErrorBag()->addError('type', $this->getTranslator()->trans('milestoneTypeInvalid'));
            }

            $programId = (int)$this->getRequest()->getPost('programId');
            if ($programId && !isset($programs[$programId])) {
                $validation->getErrorBag()->addError('programId', $this->getTranslator()->trans('entryNotFound'));
            }

            // "Program done" inside one program can only mean this one program.
            $threshold = $type === MilestoneModel::TYPE_PROGRAMS && $programId ? 1 : (int)$this->getRequest()->getPost('threshold');
            $maxThreshold = MilestoneModel::MAX_THRESHOLDS[$type] ?? 1;
            if ($threshold > $maxThreshold) {
                $validation->getErrorBag()->addError('threshold', $this->getTranslator()->trans('milestoneThresholdInvalid', $maxThreshold));
            }

            $icon = (string)$this->getRequest()->getPost('icon');
            if (!in_array($icon, MilestoneModel::ICONS, true)) {
                $validation->getErrorBag()->addError('icon', $this->getTranslator()->trans('milestoneIconInvalid'));
            }

            if ($validation->isValid()) {
                $milestone->setTitle(trim((string)$this->getRequest()->getPost('title')))
                    ->setDescription(trim((string)$this->getRequest()->getPost('description')))
                    ->setType($type)
                    ->setThreshold($threshold)
                    ->setProgramId($programId)
                    ->setIcon($icon)
                    ->setActive((bool)$this->getRequest()->getPost('active'));
                $milestoneMapper->save($milestone);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'index']);
            }

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(array_merge(['action' => 'treat'], $milestone->getId() ? ['id' => $milestone->getId()] : []));
        }

        $this->getView()->set('milestone', $milestone)
            ->set('programs', $programs);
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            (new MilestoneMapper())->delete((int)$this->getRequest()->getParam('id'));

            $this->addMessage('deleteSuccess');
        }

        $this->redirect(['action' => 'index']);
    }
}
