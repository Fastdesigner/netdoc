<?php /** @var array|null $note @var array $devices @var string $csrf @var int $preselectDevice */
$v = fn(string $k) => e($note[$k] ?? '');
$selDevice = (int) ($note['device_id'] ?? $preselectDevice);
?>
<?= ui('page-header', [
    'title' => $note ? 'Notiz bearbeiten' : 'Notiz hinzufügen',
    'description' => 'Halte Abläufe, Entscheidungen und wichtige Hinweise verständlich fest.',
]) ?>

<form class="form-panel form-panel--narrow" method="post" action="<?= url('note.save') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($note): ?><input type="hidden" name="id" value="<?= (int) $note['id'] ?>"><?php endif; ?>

    <div class="form-grid">
        <label>Titel <span class="required">Pflichtfeld</span><input type="text" name="title" value="<?= $v('title') ?>" required autofocus></label>
        <label>Zugeordnetes Gerät
            <select name="device_id">
                <option value="0">Nicht zugeordnet</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <label>Inhalt<textarea name="body" rows="12" placeholder="Was sollte dein Team hierzu wissen?"><?= $v('body') ?></textarea></label>

    <div class="form-actions">
        <?= ui('button', ['label' => 'Speichern', 'icon' => 'check', 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= ui('button', ['label' => 'Abbrechen', 'icon' => 'x', 'href' => url('notes'), 'variant' => 'quiet']) ?>
        <?php if ($note): ?>
            <?= ui('button', ['label' => 'Notiz löschen', 'icon' => 'trash-2', 'variant' => 'danger', 'type' => 'submit', 'class' => 'form-actions__danger', 'attributes' => ['formaction' => url('note.delete', ['id' => $note['id']]), 'data-confirm' => 'Notiz „' . $note['title'] . '“ wirklich löschen?']]) ?>
        <?php endif; ?>
    </div>
</form>
