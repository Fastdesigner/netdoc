<?php /** @var string $csrf @var string $step @var string $email */ ?>
<div class="card authcard">
    <h1 class="logo">Net<span>Doc</span></h1>

    <?php if (($step ?? 'email') === 'code'): ?>
        <p class="muted">Wir haben einen 6-stelligen Code an<br><strong><?= e(mask_email($email)) ?></strong> geschickt.</p>
        <form method="post" action="<?= url('login.verify') ?>" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <label>Anmeldecode
                <input type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
                       class="codeinput" autofocus autocomplete="one-time-code" placeholder="123456">
            </label>
            <button type="submit" class="btn primary block">Anmelden</button>
        </form>
        <p class="muted small">
            Keine Mail bekommen?
            <a href="<?= url('login') ?>">Erneut senden</a> ·
            <a href="<?= url('login', ['reset' => 1]) ?>">Andere Adresse</a>
        </p>
        <p class="muted small">Tipp: In der Mail ist auch ein Magic-Link – ein Klick genügt, dann musst du den Code nicht tippen.</p>
    <?php else: ?>
        <p class="muted">Passwortlose Anmeldung – gib deine E-Mail ein, wir schicken dir einen Code.</p>
        <form method="post" action="<?= url('login') ?>" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <label>E-Mail-Adresse
                <input type="email" name="email" required autofocus value="<?= e($email ?? '') ?>" placeholder="du@example.com">
            </label>
            <button type="submit" class="btn primary block">Code anfordern</button>
        </form>
    <?php endif; ?>
</div>
