<?php /** @var string $message */ ?>
<?= ui('page-header', ['title' => 'Das hat nicht geklappt', 'description' => $message]) ?>
<?= ui('empty-state', [
    'icon' => 'alert-circle',
    'title' => 'Seite nicht verfügbar',
    'text' => 'Kehre zur Übersicht zurück und versuche es von dort erneut.',
    'action' => ['label' => 'Zur Übersicht', 'icon' => 'arrow-left', 'href' => url('home')],
]) ?>
