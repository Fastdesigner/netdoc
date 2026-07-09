<?php
/** Wiederverwendbare Zugangs-Tabelle. Erwartet $creds (Array von Zeilen). */
?>
<div class="tablewrap">
<table class="table">
    <thead><tr><th>Titel</th><th>Kategorie</th><th>Benutzer</th><th>Passwort</th><th>URL / Ziel</th><th>Zuordnung</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($creds as $c): ?>
        <tr>
            <td data-label="Titel"><strong><?= e($c['title']) ?></strong>
                <?php if (($c['visibility'] ?? 'team') === 'private'): ?>
                    <div><span class="tag warn">privat<?= !empty($c['owner_name']) ? ': ' . e($c['owner_name']) : '' ?></span></div>
                <?php endif; ?>
            </td>
            <td data-label="Kategorie"><span class="tag"><?= e($c['category']) ?></span></td>
            <td data-label="Benutzer" class="mono"><?php if ($c['username']): ?>
                <span class="copy" data-copy="<?= e($c['username']) ?>"><?= e($c['username']) ?></span>
            <?php else: ?>–<?php endif; ?></td>
            <td data-label="Passwort" class="secretcell">
                <?php if ($c['secret_enc']): ?>
                    <code class="secret" data-id="<?= (int) $c['id'] ?>">••••••••</code>
                    <button type="button" class="linkbtn reveal" data-id="<?= (int) $c['id'] ?>">anzeigen</button>
                <?php else: ?><span class="muted">–</span><?php endif; ?>
            </td>
            <td data-label="URL / Ziel"><?php if ($c['url']): ?>
                <span class="mono"><?= e($c['url']) ?></span><?php if ($c['port']): ?><span class="muted">:<?= e($c['port']) ?></span><?php endif; ?>
            <?php else: ?>–<?php endif; ?></td>
            <td data-label="Zuordnung">
                <?php if (!empty($c['device_name'])): ?><div><span class="tag"><?= e($c['device_name']) ?></span></div><?php endif; ?>
                <?php if (!empty($c['product_name'])): ?><div><span class="tag"><?= e($c['product_name']) ?></span></div><?php endif; ?>
                <?php if (empty($c['device_name']) && empty($c['product_name'])): ?>–<?php endif; ?>
            </td>
            <td data-label="Aktion" class="rowactions"><a href="<?= url('cred.edit', ['id' => $c['id']]) ?>">Bearbeiten</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
