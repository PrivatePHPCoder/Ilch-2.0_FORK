<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Validation;
use Ilch\Validation\ErrorBag;
use Modules\Fitness\Models\PaymentOptions;
use Modules\Fitness\Service\PayPal;

class Settings extends Base
{
    /**
     * Client ids and secrets of PayPal apps: letters, digits, "-" and "_".
     */
    private const PAYPAL_KEY_PATTERN = '/^[A-Za-z0-9_-]{10,200}$/';

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

            $paypal = $this->readPaypalSettings($validation->getErrorBag(), $validation->isValid());

            if ($validation->isValid()) {
                $this->getConfig()->set('fitness_ownLayout', $this->getRequest()->getPost('ownLayout'))
                    ->set('fitness_payTransfer', (string)$payTransfer)
                    ->set('fitness_bankDetails', $bankDetails)
                    ->set('fitness_payPaypalMe', $paypalMe)
                    ->set('fitness_paymentInfo', trim((string)$this->getRequest()->getPost('paymentInfo')))
                    ->set('fitness_orderNotifyEmail', $notifyEmail)
                    ->set('fitness_paypalCheckout', $paypal['checkout'] ? '1' : '0')
                    ->set('fitness_paypalSandbox', $paypal['sandbox'] ? '1' : '0')
                    ->set('fitness_paypalClientId', $paypal['clientId']);
                if ($paypal['secret'] !== null) {
                    $this->getConfig()->set('fitness_paypalSecret', $paypal['secret']);
                }

                $this->redirect()
                    ->withMessage('saveSuccess')
                    ->to(['action' => 'index']);
            }

            // The secret never goes into the session for the form.
            $input = $this->getRequest()->getPost();
            unset($input['paypalSecret']);

            $this->addMessage($validation->getErrorBag()->getErrorMessages(), 'danger', true);
            $this->redirect()
                ->withInput($input)
                ->withErrors($validation->getErrorBag())
                ->to(['action' => 'index']);
        }

        $this->getView()->set('ownLayout', $this->getConfig()->get('fitness_ownLayout'))
            ->set('payment', PaymentOptions::fromConfig($this->getConfig()))
            ->set('orderNotifyEmail', (string)$this->getConfig()->get('fitness_orderNotifyEmail'));
    }

    /**
     * Reads and checks the PayPal Checkout settings. With PayPal Checkout switched on, PayPal is
     * asked whether it accepts client id and secret.
     *
     * @param ErrorBag $errorBag
     * @param bool $otherInputValid whether the other settings are valid, PayPal is only asked then
     * @return array{checkout: bool, sandbox: bool, clientId: string, secret: string|null} secret null keeps the stored one
     */
    private function readPaypalSettings(ErrorBag $errorBag, bool $otherInputValid): array
    {
        $checkout = (int)$this->getRequest()->getPost('paypalCheckout') === 1;
        $sandbox = (int)$this->getRequest()->getPost('paypalSandbox') === 1;
        $clientId = trim((string)$this->getRequest()->getPost('paypalClientId'));
        $newSecret = trim((string)$this->getRequest()->getPost('paypalSecret'));

        if ($clientId !== '' && !preg_match(self::PAYPAL_KEY_PATTERN, $clientId)) {
            $errorBag->addError('paypalClientId', $this->getTranslator()->trans('paypalClientIdInvalid'));
        }
        if ($newSecret !== '' && !preg_match(self::PAYPAL_KEY_PATTERN, $newSecret)) {
            $errorBag->addError('paypalSecret', $this->getTranslator()->trans('paypalSecretInvalid'));
        }

        $secret = $newSecret !== '' ? $newSecret : (string)$this->getConfig()->get('fitness_paypalSecret', true);
        if ($checkout && ($clientId === '' || $secret === '')) {
            $errorBag->addError('paypalClientId', $this->getTranslator()->trans('paypalCredentialsMissing'));
        } elseif ($checkout && $otherInputValid && !$errorBag->hasErrors() && !(new PayPal($clientId, $secret, $sandbox))->testCredentials()) {
            $errorBag->addError('paypalSecret', $this->getTranslator()->trans($sandbox ? 'paypalCredentialsRejectedSandbox' : 'paypalCredentialsRejectedLive'));
        }

        return [
            'checkout' => $checkout,
            'sandbox' => $sandbox,
            'clientId' => $clientId,
            // Without a client id the stored secret is removed as well.
            'secret' => $clientId === '' ? '' : ($newSecret !== '' ? $newSecret : null),
        ];
    }
}
