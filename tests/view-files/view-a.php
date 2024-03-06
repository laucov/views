<?php /** @var \Laucov\Views\View $this */?>
<main>
    <?php if ($name ?? null): ?>
        <p>Hello, <?=$name?>!</p>
    <?php else: ?>
        <p>Greetings, Stranger!</p>
    <?php endif; ?>
</main>