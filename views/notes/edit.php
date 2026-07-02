<?php /** @var array|null $note @var array $devices @var string $csrf @var int $preselectDevice */
$v = fn(string $k) => e($note[$k] ?? '');
$selDevice = (int) ($note['device_id'] ?? $preselectDevice);
?>
<div class="pagehead">
    <h1><?= $note ? 'Notiz bearbeiten' : 'Neue Notiz' ?></h1>
</div>

<form class="card form" method="post" action="<?= url('note.save') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($note): ?><input type="hidden" name="id" value="<?= (int) $note['id'] ?>"><?php endif; ?>

    <div class="grid2">
        <label>Titel *<input type="text" name="title" value="<?= $v('title') ?>" required autofocus></label>
        <label>Gerät / Server
            <select name="device_id">
                <option value="0">— keins —</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <label>Inhalt<textarea name="body" rows="12"><?= $v('body') ?></textarea></label>

    <div class="formactions">
        <button type="submit" class="btn primary">Speichern</button>
        <a class="btn ghost" href="<?= url('notes') ?>">Abbrechen</a>
        <?php if ($note): ?>
            <button type="submit" class="btn danger right" formaction="<?= url('note.delete', ['id' => $note['id']]) ?>"
                    data-confirm="Notiz „<?= e($note['title']) ?>“ wirklich löschen?">Löschen</button>
        <?php endif; ?>
    </div>
</form>
