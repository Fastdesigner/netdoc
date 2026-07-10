<?php /** @var string $csrf @var string $step @var string $email */ ?>
<section class="auth-card">
    <header class="auth-card__header">
        <div class="logo">Net<span>Doc</span></div>
        <span class="auth-card__mark"><?= ui('icon', ['name' => 'shield-check']) ?></span>
    </header>

    <?php if (($step ?? 'email') === 'code'): ?>
        <div class="auth-card__intro">
            <h1>Code eingeben</h1>
            <p>Wir haben einen sechsstelligen Code an <strong><?= e(mask_email($email)) ?></strong> geschickt.</p>
        </div>
        <form method="post" action="<?= url('login.verify') ?>" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <label>Anmeldecode
                <input type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
                       class="code-input" autofocus autocomplete="one-time-code" placeholder="000 000" required>
            </label>
            <?= ui('button', ['label' => 'Sicher anmelden', 'icon' => 'check', 'variant' => 'primary', 'type' => 'submit', 'class' => 'button--block']) ?>
        </form>
        <div class="auth-card__alternatives">
            <form method="post" action="<?= url('login') ?>">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="email" value="<?= e($email) ?>">
                <button type="submit" class="text-button">Code erneut senden</button>
            </form>
            <a href="<?= url('login', ['reset' => 1]) ?>">Andere E-Mail-Adresse</a>
        </div>
        <p class="auth-card__hint">Du kannst auch einfach den Anmeldelink in der E-Mail öffnen.</p>
    <?php else: ?>
        <div class="auth-card__intro">
            <h1>Willkommen zurück</h1>
            <p>Gib deine hinterlegte E-Mail-Adresse ein. Wir schicken dir sofort einen Anmeldecode.</p>
        </div>
        <form method="post" action="<?= url('login') ?>" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <label>E-Mail-Adresse
                <input type="email" name="email" required autofocus value="<?= e($email ?? '') ?>" placeholder="du@example.com">
            </label>
            <?= ui('button', ['label' => 'Anmeldecode senden', 'icon' => 'key-round', 'variant' => 'primary', 'type' => 'submit', 'class' => 'button--block auth-submit']) ?>
        </form>
    <?php endif; ?>
</section>
