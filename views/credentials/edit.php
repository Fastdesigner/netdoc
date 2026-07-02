<?php /** @var array|null $cred @var array $devices @var array $categories @var string $csrf @var int $preselectDevice */
$v = fn(string $k) => e($cred[$k] ?? '');
$selDevice = (int) ($cred['device_id'] ?? $preselectDevice);
?>
<div class="pagehead">
    <h1><?= $cred ? 'Zugang bearbeiten' : 'Neuer Zugang' ?></h1>
</div>

<form class="card form" method="post" action="<?= url('cred.save') ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($cred): ?><input type="hidden" name="id" value="<?= (int) $cred['id'] ?>"><?php endif; ?>

    <div class="grid2">
        <label>Titel *<input type="text" name="title" value="<?= $v('title') ?>" required autofocus placeholder="z.B. Firewall Admin"></label>
        <label>Kategorie
            <select name="category">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= ($cred['category'] ?? 'login') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Gerät / Server
            <select name="device_id">
                <option value="0">— keins —</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Benutzername<input type="text" name="username" value="<?= $v('username') ?>" class="mono" autocomplete="off"></label>
        <label>Passwort / Secret
            <div class="pwfield">
                <input type="password" name="secret" class="mono" autocomplete="new-password"
                       placeholder="<?= $cred && $cred['secret_enc'] ? '•••• (leer lassen = unverändert)' : '' ?>">
                <button type="button" class="linkbtn togglepw">zeigen</button>
            </div>
        </label>
        <label>URL / Ziel<input type="text" name="url" value="<?= $v('url') ?>" class="mono" placeholder="https://… oder ssh://host"></label>
        <label>Port<input type="text" name="port" value="<?= $v('port') ?>" class="mono" placeholder="443"></label>
    </div>
    <label>Notizen<textarea name="notes" rows="3"><?= $v('notes') ?></textarea></label>

    <div class="formactions">
        <button type="submit" class="btn primary">Speichern</button>
        <a class="btn ghost" href="<?= url('creds') ?>">Abbrechen</a>
        <?php if ($cred): ?>
            <button type="submit" class="btn danger right" formaction="<?= url('cred.delete', ['id' => $cred['id']]) ?>"
                    data-confirm="Zugang „<?= e($cred['title']) ?>“ wirklich löschen?">Löschen</button>
        <?php endif; ?>
    </div>
</form>
