<?php /** @var $form \GDO\Form\GDT_Form **/
$firstEditableField = null;
?>
<!-- Begin Form -->
<div class="gdo-form <?=$form->htmlClassSlim()?>">
	<div class="gdo-form-inner">
<?php if ($form->hasTitle() || $form->info) : ?>
	  <div class="gdo-form-head">
<?php if ($form->hasTitle()) : ?>
		<h2 class="gdo-form-title"><?=$form->renderTitle()?></h2>
<?php endif; ?>
<?php if ($form->info) : ?>
		<p><?= $form->info; ?></p>
<?php endif; ?>
	  </div>
<?php endif; ?>
	  <form
	   id="gdo_<?=$form->name;?>"
	   action="<?= $form->action; ?>"
	   method="<?= $form->method; ?>"
	   enctype="<?= $form->encoding; ?>">
	  <?php if ($form->method === 'GET') : ?>
	    <?=$form->htmlHidden()?>
	  <?php endif; ?>
<?php foreach ($form->getFields() as $field) : ?>
          <?php if ($field->editable && (!$field->initial)) $firstEditableField = $firstEditableField ? $firstEditableField : $field; ?>
			<?= $field->gdo($form->gdo)->renderForm(); ?>
		  <?php endforeach; ?>
	  </form>
	</div>
</div>
<script type="text/javascript">
window.GDO_FIRST_EDITABLE_FIELD = window.GDO_FIRST_EDITABLE_FIELD||'<?=$firstEditableField?$firstEditableField->id():null?>';
</script>
<!-- End of Form -->
