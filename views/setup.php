<?php /** @var string $csrf @var bool $configExists */ ?>
<section class="auth-card auth-card--wide">
    <header class="auth-card__header">
        <div class="logo">Net<span>Doc</span></div>
        <span class="auth-card__mark"><?= ui('icon', ['name' => 'shield-check']) ?></span>
    </header>
    <div class="auth-card__intro">
        <h1>NetDoc einrichten</h1>
        <p>Lege dein Administratorkonto an. Danach kannst du Geräte, Zugänge und Dokumente erfassen.</p>
    </div>

    <?php if (!is_writable(CONFIG)): ?>
        <div class="inline-alert inline-alert--error">
            <?= ui('icon', ['name' => 'alert-circle']) ?>
            <div>
            <strong>Konfiguration nicht beschreibbar</strong>
            Verzeichnis <code>config/</code> ist nicht beschreibbar. Bitte Schreibrechte setzen
            (z.&nbsp;B. <code>chmod 750 config</code>), sonst kann der Krypto-Schlüssel nicht gespeichert werden.
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('setup') ?>" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Benutzername
            <input type="text" name="username" required autofocus minlength="3">
        </label>
        <label>E-Mail-Adresse <small>(für deine Anmeldecodes)</small>
            <input type="email" name="email" required placeholder="du@example.com">
        </label>
        <?= ui('button', ['label' => 'Einrichten und starten', 'icon' => 'check', 'variant' => 'primary', 'type' => 'submit', 'class' => 'button--block']) ?>
    </form>
    <p class="auth-card__hint">NetDoc schützt deine gespeicherten Geheimnisse automatisch mit einem eigenen Verschlüsselungsschlüssel.</p>
</section>
