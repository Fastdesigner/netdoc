<?php /** @var array $creds */ ?>
<div class="table-wrap">
<table class="data-table data-table--credentials">
    <thead><tr><th>Titel</th><th>Kategorie</th><th>Benutzer</th><th>Passwort</th><th>Adresse</th><th>Zuordnung</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($creds as $c): ?>
        <tr>
            <td data-label="Titel" class="data-table__primary">
                <strong><?= e($c['title']) ?></strong>
                <?php if (($c['visibility'] ?? 'team') === 'private'): ?>
                    <div><span class="badge badge--warning">Privat<?= !empty($c['owner_name']) ? ' · ' . e($c['owner_name']) : '' ?></span></div>
                <?php endif; ?>
            </td>
            <td data-label="Kategorie"><span class="badge"><?= e(\NetDoc\credential_category_label($c['category'])) ?></span></td>
            <td data-label="Benutzer">
                <?php if ($c['username']): ?>
                    <span class="copy-field"><code><?= e($c['username']) ?></code><?= ui('button', ['label' => 'Benutzername kopieren', 'icon' => 'copy', 'variant' => 'quiet', 'size' => 'small', 'class' => 'copy-action button--icon-only', 'attributes' => ['data-copy' => $c['username'], 'title' => 'Benutzername kopieren', 'aria-label' => 'Benutzername kopieren']]) ?></span>
                <?php else: ?>–<?php endif; ?>
            </td>
            <td data-label="Passwort" class="secret-cell">
                <?php if ($c['secret_enc']): ?>
                    <code class="secret" data-id="<?= (int) $c['id'] ?>">••••••••</code>
                    <?= ui('button', ['label' => 'Anzeigen', 'icon' => 'eye', 'variant' => 'quiet', 'size' => 'small', 'class' => 'reveal', 'attributes' => ['data-id' => (int) $c['id']]]) ?>
                <?php else: ?><span class="muted">–</span><?php endif; ?>
            </td>
            <td data-label="Adresse"><?php if ($c['url']): ?>
                <span class="address-value"><span class="mono data-address"><?= e($c['url']) ?></span><?php if ($c['port']): ?><span class="muted">:<?= e($c['port']) ?></span><?php endif; ?></span>
            <?php else: ?>–<?php endif; ?></td>
            <td data-label="Zuordnung" class="assignment-cell">
                <?php if (!empty($c['device_name'])): ?><span class="badge"><?= ui('icon', ['name' => 'server']) ?><?= e($c['device_name']) ?></span><?php endif; ?>
                <?php if (!empty($c['product_name'])): ?><span class="badge"><?= ui('icon', ['name' => 'package']) ?><?= e($c['product_name']) ?></span><?php endif; ?>
                <?php if (empty($c['device_name']) && empty($c['product_name'])): ?>–<?php endif; ?>
            </td>
            <td data-label="Aktion" class="row-actions"><?= ui('button', ['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('cred.edit', ['id' => $c['id']]), 'variant' => 'quiet', 'size' => 'small']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
