<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Modules\Fitness\Mappers\Category as CategoryMapper;
use Modules\Fitness\Models\Category as CategoryModel;

class Categories extends Base
{
    public function indexAction()
    {
        $categoryMapper = new CategoryMapper();

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuCategories'), ['action' => 'index']);

        if ($this->getRequest()->getPost('action') === 'delete' && $this->getRequest()->getPost('check_categories')) {
            foreach ($this->getRequest()->getPost('check_categories') as $id) {
                $categoryMapper->delete((int)$id);
            }

            $this->redirect()
                ->withMessage('deleteSuccess')
                ->to(['action' => 'index']);
        }

        if ($this->getRequest()->getPost('saveOrder')) {
            $categoryMapper->updatePositions((array)$this->getRequest()->getPost('items'));

            $this->redirect()
                ->withMessage('saveSuccess')
                ->to(['action' => 'index']);
        }

        $this->getView()->set('categories', $categoryMapper->getCategories())
            ->set('sampleIds', $this->getSampleIds('categories'));
    }

    public function treatAction()
    {
        $categoryMapper = new CategoryMapper();
        $category = new CategoryModel();

        if ($this->getRequest()->getParam('id')) {
            $category = $categoryMapper->getCategoryById((int)$this->getRequest()->getParam('id'));

            if (!$category) {
                $this->redirect()
                    ->withMessage('entryNotFound', 'danger')
                    ->to(['action' => 'index']);
            }
        }

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuCategories'), ['action' => 'index'])
            ->add($this->getTranslator()->trans($category->getId() ? 'edit' : 'add'), array_merge(['action' => 'treat'], $category->getId() ? ['id' => $category->getId()] : []));

        if ($this->getRequest()->isPost()) {
            $validation = Validation::create($this->getRequest()->getPost(), [
                'name' => 'required|max:100,string',
            ]);

            if ($validation->isValid()) {
                $category->setName(trim($this->getRequest()->getPost('name')));
                $categoryMapper->save($category);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'index']);
            }

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(array_merge(['action' => 'treat'], $category->getId() ? ['id' => $category->getId()] : []));
        }

        $this->getView()->set('category', $category);
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            $categoryMapper = new CategoryMapper();
            $categoryMapper->delete((int)$this->getRequest()->getParam('id'));

            $this->addMessage('deleteSuccess');
        }

        $this->redirect(['action' => 'index']);
    }
}
