<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\PaymentOptions $payment */
$payment = $this->get('payment');
$payTransfer = (int)$this->originalInput('payTransfer', (int)$payment->isTransferSwitchedOn());
?>
<h1><?=$this->getTrans('menuSettings') ?></h1>
<form method="POST">
    <?=$this->getTokenField() ?>
    <div class="row mb-3<?=$this->validation()->hasError('ownLayout') ? ' has-error' : '' ?>">
        <div class="col-xl-2 col-form-label">
            <?=$this->getTrans('ownLayout') ?>
        </div>
        <div class="col-xl-4">
            <div class="flipswitch">
                <input type="radio" class="flipswitch-input" id="ownLayout-on" name="ownLayout" value="1" <?=($this->originalInput('ownLayout', $this->get('ownLayout')) == '1') ? 'checked="checked"' : '' ?> />
                <label for="ownLayout-on" class="flipswitch-label flipswitch-label-on"><?=$this->getTrans('on') ?></label>
                <input type="radio" class="flipswitch-input" id="ownLayout-off" name="ownLayout" value="0" <?=($this->originalInput('ownLayout', $this->get('ownLayout')) != '1') ? 'checked="checked"' : '' ?> />
                <label for="ownLayout-off" class="flipswitch-label flipswitch-label-off"><?=$this->getTrans('off') ?></label>
                <span class="flipswitch-selection"></span>
            </div>
        </div>
        <div class="col-xl-6 form-text">
            <?=$this->getTrans('ownLayoutInfo') ?>
        </div>
    </div>

    <h2 class="h4 mt-4"><?=$this->getTrans('paymentSettings') ?></h2>
    <p class="text-muted"><?=$this->getTrans('paymentSettingsInfo') ?></p>

    <div class="row mb-3<?=$this->validation()->hasError('payTransfer') ? ' has-error' : '' ?>">
        <div class="col-xl-2 col-form-label">
            <?=$this->getTrans('paymentTransfer') ?>
        </div>
        <div class="col-xl-4">
            <div class="flipswitch">
                <input type="radio" class="flipswitch-input" id="payTransfer-on" name="payTransfer" value="1"<?=$payTransfer === 1 ? ' checked' : '' ?>>
                <label for="payTransfer-on" class="flipswitch-label flipswitch-label-on"><?=$this->getTrans('on') ?></label>
                <input type="radio" class="flipswitch-input" id="payTransfer-off" name="payTransfer" value="0"<?=$payTransfer !== 1 ? ' checked' : '' ?>>
                <label for="payTransfer-off" class="flipswitch-label flipswitch-label-off"><?=$this->getTrans('off') ?></label>
                <span class="flipswitch-selection"></span>
            </div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('bankDetails') ? ' has-error' : '' ?>">
        <label for="bankDetails" class="col-xl-2 col-form-label">
            <?=$this->getTrans('bankDetails') ?>
        </label>
        <div class="col-xl-6">
            <textarea class="form-control" id="bankDetails" name="bankDetails" rows="4" maxlength="1000" placeholder="<?=$this->getTrans('bankDetailsPlaceholder') ?>"><?=$this->escape($this->originalInput('bankDetails', $payment->getBankDetails())) ?></textarea>
            <div class="form-text"><?=$this->getTrans('bankDetailsInfo') ?></div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('payPaypalMe') ? ' has-error' : '' ?>">
        <label for="payPaypalMe" class="col-xl-2 col-form-label">
            <?=$this->getTrans('paymentPaypalMe') ?>
        </label>
        <div class="col-xl-4">
            <div class="input-group">
                <span class="input-group-text">paypal.me/</span>
                <input type="text" class="form-control" id="payPaypalMe" name="payPaypalMe" maxlength="100" value="<?=$this->escape($this->originalInput('payPaypalMe', $payment->getPaypalMeName())) ?>">
            </div>
        </div>
        <div class="col-xl-6 form-text"><?=$this->getTrans('paypalMeInfo') ?></div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('paymentInfo') ? ' has-error' : '' ?>">
        <label for="paymentInfo" class="col-xl-2 col-form-label">
            <?=$this->getTrans('paymentInfo') ?>
        </label>
        <div class="col-xl-6">
            <textarea class="form-control" id="paymentInfo" name="paymentInfo" rows="2" maxlength="1000"><?=$this->escape($this->originalInput('paymentInfo', $payment->getInfo())) ?></textarea>
            <div class="form-text"><?=$this->getTrans('paymentInfoInfo') ?></div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('orderNotifyEmail') ? ' has-error' : '' ?>">
        <label for="orderNotifyEmail" class="col-xl-2 col-form-label">
            <?=$this->getTrans('orderNotifyEmail') ?>
        </label>
        <div class="col-xl-4">
            <input type="email" class="form-control" id="orderNotifyEmail" name="orderNotifyEmail" maxlength="255" value="<?=$this->escape($this->originalInput('orderNotifyEmail', $this->get('orderNotifyEmail'))) ?>">
        </div>
        <div class="col-xl-6 form-text"><?=$this->getTrans('orderNotifyEmailInfo') ?></div>
    </div>

    <?=$this->getSaveBar() ?>
</form>
