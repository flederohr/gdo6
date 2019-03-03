<?php
use GDO\Language\GDO_Language;
/**
 * @var $language GDO_Language
 */
var_dump($language);
?>
<img
class="gdo-language"
	alt="<?= $language->displayName(); ?>"
	title="<?= $language->displayName(); ?>"
	src="GDO/Language/img/<?= $language->getID(); ?>.png" />
	