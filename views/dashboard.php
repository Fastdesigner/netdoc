<?php /** @var array $counts @var array $recent @var array $expiring */ ?>
<div class="pagehead">
    <h1>Übersicht</h1>
</div>

<div class="statgrid">
    <a class="stat" href="<?= url('devices') ?>"><span class="num"><?= (int) $counts['devices'] ?></span>Geräte &amp; Server</a>
    <a class="stat" href="<?= url('creds') ?>"><span class="num"><?= (int) $counts['creds'] ?></span>Zugänge</a>
    <a class="stat" href="<?= url('products') ?>"><span class="num"><?= (int) $counts['products'] ?></span>Produkte</a>
    <a class="stat" href="<?= url('notes') ?>"><span class="num"><?= (int) $counts['notes'] ?></span>Notizen</a>
</div>

<div class="cols">
    <section class="card">
        <h2>Zuletzt geändert</h2>
        <?php if (!$recent): ?>
            <p class="muted">Noch keine Geräte. <a href="<?= url('device.edit') ?>">Erstes Gerät anlegen →</a></p>
        <?php else: ?>
            <ul class="linklist">
                <?php foreach ($recent as $d): ?>
                    <li>
                        <a href="<?= url('device.view', ['id' => $d['id']]) ?>"><?= e($d['name']) ?></a>
                        <span class="tag"><?= e($d['type']) ?></span>
                        <?php if ($d['ip']): ?><span class="muted mono"><?= e($d['ip']) ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Ablaufende Produkte / Lizenzen</h2>
        <?php if (!$expiring): ?>
            <p class="muted">Keine Ablaufdaten hinterlegt.</p>
        <?php else: ?>
            <ul class="linklist">
                <?php foreach ($expiring as $p): ?>
                    <li>
                        <a href="<?= url('product.edit', ['id' => $p['id']]) ?>"><?= e($p['name']) ?></a>
                        <span class="tag warn"><?= e($p['expiry_date']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
