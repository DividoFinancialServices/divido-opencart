<div class="divido-calculator divido-theme-blue" data-divido-calculator data-divido-plans="<?php echo $planList; ?>" data-divido-amount="<?php echo $productPrice; ?>">
    <h1>
        <a href="https://www.divido.com" target="_blank" class="divido-logo divido-logo-sm" style="float:right;">Divido</a>
        Pay in installments
    </h1>
    <dl>
        <dt><span data-divido-choose-finance data-divido-label="Choose your plan" data-divido-form="finance"></span></dt>
        <dd><span class="divido-deposit" data-divido-choose-deposit data-divido-label="Choose your deposit" data-divido-form="deposit"></span></dd>
    </dl>
    <div class="description"><strong><span data-divido-agreement-duration></span> monthly payments of <span data-divido-monthly-instalment></span></strong></div>
    <div class="divido-info">
        <dl>
            <dt>Term</dt>
            <dd><span data-divido-agreement-duration></span> months</dd>
            <dt>Monthly instalment</dt>
            <dd><span data-divido-monthly-instalment></span></dd>
            <dt>Deposit</dt>
            <dd><span data-divido-deposit></span></dd>
            <dt>Amount of credit</dt>
            <dd><span data-divido-credit-amount></span></dd>
            <dt>Total payable</dt>
            <dd><span data-divido-total-payable></span></dd>
            <dt>Total interest APR</dt>
            <dd><span data-divido-interest-rate></span></dd>
        </dl>
    </div>
    <div class="clear"></div>
</div>
