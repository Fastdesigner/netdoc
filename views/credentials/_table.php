<?php
/** Wiederverwendbare Zugangs-Tabelle. Erwartet $creds (Array von Zeilen). */
?>
<div class="tablewrap">
<table class="table">
    <thead><tr><th>Titel</th><th>Kategorie</th><th>Benutzer</th><th>Passwort</th><th>URL / Ziel</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($creds as $c): ?>
        <tr>
            <td><strong><?= e($c['title']) ?></strong>
                <?php if (!empty($c['device_name'])): ?><div class="muted small"><?= e($c['device_name']) ?></div><?php endif; ?>
            </td>
            <td><span class="tag"><?= e($c['category']) ?></span></td>
            <td class="mono"><?php if ($c['username']): ?>
                <span class="copy" data-copy="<?= e($c['username']) ?>"><?= e($c['username']) ?></span>
            <?php else: ?>–<?php endif; ?></td>
            <td class="secretcell">
                <?php if ($c['secret_enc']): ?>
                    <code class="secret" data-id="<?= (int) $c['id'] ?>">••••••••</code>
                    <button type="button" class="linkbtn reveal" data-id="<?= (int) $c['id'] ?>">anzeigen</button>
                <?php else: ?><span class="muted">–</span><?php endif; ?>
            </td>
            <td><?php if ($c['url']): ?>
                <span class="mono"><?= e($c['url']) ?></span><?php if ($c['port']): ?><span class="muted">:<?= e($c['port']) ?></span><?php endif; ?>
            <?php else: ?>–<?php endif; ?></td>
            <td class="rowactions"><a href="<?= url('cred.edit', ['id' => $c['id']]) ?>">Bearbeiten</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
