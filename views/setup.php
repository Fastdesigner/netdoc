<?php /** @var string $csrf @var bool $configExists */ ?>
<div class="card authcard">
    <h1 class="logo">Net<span>Doc</span></h1>
    <p class="muted">Erstinstallation – Administrator anlegen</p>

    <?php if (!is_writable(CONFIG)): ?>
        <div class="flash error">
            Verzeichnis <code>config/</code> ist nicht beschreibbar. Bitte Schreibrechte setzen
            (z.&nbsp;B. <code>chmod 750 config</code>), sonst kann der Krypto-Schlüssel nicht gespeichert werden.
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('setup') ?>" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Benutzername
            <input type="text" name="username" required autofocus minlength="3">
        </label>
        <label>Passwort <small class="muted">(mind. 10 Zeichen)</small>
            <input type="password" name="password" required minlength="10">
        </label>
        <label>Passwort wiederholen
            <input type="password" name="password2" required minlength="10">
        </label>
        <button type="submit" class="btn primary block">Einrichten &amp; loslegen</button>
    </form>
    <p class="muted small">Beim Einrichten wird automatisch ein Verschlüsselungs-Schlüssel erzeugt und in <code>config/config.php</code> abgelegt.</p>
</div>
