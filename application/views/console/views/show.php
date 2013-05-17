<h1>Exibição de <?= ucfirst($name) ?></h1>

<?php 
foreach ($attrs as $attr): if($attr['name'] != 'belong_to'): 
?>

<p>
	<strong><?= ucfirst($attr['name']) ?>: </strong> </?= $<?= $name ?>-><?= $attr['name'] ?> ?>
</p>
<?php else: ?>

<p>
	<strong>ID do <?= ucfirst(singular($attr['properties'])) ?></strong> <a href="</?= site_url('<?= plural($attr['properties']) ?>/show/' .  $<?= $name ?>-><?= $attr['properties'] ?>_id) ?>"></?= $<?= $name ?>-><?= $attr['properties'] ?>_id ?></a>
</p>
<?php
	endif; 
endforeach; 
?>

<a class="btn" href="</?= site_url('<?= plural($name) ?>/edit/' . $<?= $name ?>->id) ?>">Editar</a>
<a class="btn" href="</?= site_url('<?= plural($name) ?>/') ?>">Voltar</a>