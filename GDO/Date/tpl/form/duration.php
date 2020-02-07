<?php /** @var $field \GDO\DB\GDT_Duration **/ ?>
<div class="gdo-container<?= $field->classError(); ?>">
  <?=$field->htmlTooltip()?>
  <?= $field->htmlIcon(); ?>
  <label for="form[<?= $field->name; ?>]"><?= $field->displayLabel(); ?></label>
  <input
   type="text"
   name="form[<?= $field->name; ?>]"
   <?= $field->htmlDisabled(); ?>
   <?= $field->htmlRequired(); ?>
   value="<?= $field->displayVar(); ?>" />
  <?= $field->htmlError(); ?>
</div>