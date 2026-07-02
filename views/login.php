<?php /** @var string $csrf */ ?>
<div class="card authcard">
    <h1 class="logo">Net<span>Doc</span></h1>
    <p class="muted">IT-Dokumentation · Anmeldung erforderlich</p>
    <form method="post" action="<?= url('login') ?>" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Benutzername
            <input type="text" name="username" required autofocus>
        </label>
        <label>Passwort
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn primary block">Anmelden</button>
    </form>
</div>
