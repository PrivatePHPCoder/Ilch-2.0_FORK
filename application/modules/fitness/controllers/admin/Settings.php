<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Modules\Fitness\Models\PaymentOptions;

class Settings extends Base
{
    public function indexAction()
    {
        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuSettings'), ['action' => 'index']);

        if ($this->getRequest()->isPost()) {
            $validation = Validation::create($this->getRequest()->getPost(), [
                'ownLayout' => 'required|numeric|integer|min:0|max:1',
                'payTransfer' => 'required|numeric|integer|min:0|max:1',
                'bankDetails' => 'max:1000,string',
                'paymentInfo' => 'max:1000,string',
                'orderNotifyEmail' => 'max:255,string',
            ]);

            $payTransfer = (int)$this->getRequest()->getPost('payTransfer');
            $bankDetails = trim((string)$this->getRequest()->getPost('bankDetails'));
            if ($payTransfer && $bankDetails === '') {
                $validation->getErrorBag()->addError('bankDetails', $this->getTranslator()->trans('bankDetailsRequired'));
            }

            $paypalMe = PaymentOptions::normalizePaypalMeName((string)$this->getRequest()->getPost('payPaypalMe'));
            if ($paypalMe === null) {
                $validation->getErrorBag()->addError('payPaypalMe', $this->getTranslator()->trans('paypalMeInvalid'));
            }

            $notifyEmail = trim((string)$this->getRequest()->getPost('orderNotifyEmail'));
            if ($notifyEmail !== '' && !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
                $validation->getErrorBag()->addError('orderNotifyEmail', $this->getTranslator()->trans('orderNotifyEmailInvalid'));
            }

            if ($validation->isValid()) {
                $this->getConfig()->set('fitness_ownLayout', $this->getRequest()->getPost('ownLayout'))
                    ->set('fitness_payTransfer', (string)$payTransfer)
                    ->set('fitness_bankDetails', $bankDetails)
                    ->set('fitness_payPaypalMe', $paypalMe)
                    ->set('fitness_paymentInfo', trim((string)$this->getRequest()->getPost('paymentInfo')))
                    ->set('fitness_orderNotifyEmail', $notifyEmail);

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'index']);
            }

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput()
                ->withErrors($validation->getErrorBag())
                ->to(['action' => 'index']);
        }

        $this->getView()->set('ownLayout', $this->getConfig()->get('fitness_ownLayout'))
            ->set('payment', PaymentOptions::fromConfig($this->getConfig()))
            ->set('orderNotifyEmail', (string)$this->getConfig()->get('fitness_orderNotifyEmail'));
    }
}
