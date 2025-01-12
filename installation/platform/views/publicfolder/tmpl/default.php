<?php
/**
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die();

/** @var AngieViewPublicfolder $this */

$document = $this->container->application->getDocument();

$document->addScript('angie/js/json.min.js');
$document->addScript('angie/js/ajax.min.js');
$document->addScript('platform/js/publicfolder.min.js');

$url = 'index.php';

$document->addScriptDeclaration(<<<JS
var akeebaAjax = null;

akeeba.System.documentReady(function(){
	akeebaAjax = new akeebaAjaxConnector('$url');
});

JS
);

echo $this->loadAnyTemplate('steps/buttons');
echo $this->loadAnyTemplate('steps/steps', ['helpurl' => 'https://www.akeeba.com/documentation/solo/angie-joomla-publicfolder.html']);
?>

<div class="akeeba-panel--teal">
	<header class="akeeba-block-header">
		<h3>
			<?= AText::_('PUBLICFOLDER_LBL_INFO_HEAD') ?>
		</h3>
	</header>
	<p>
		<?= AText::sprintf('PUBLICFOLDER_LBL_INFO_BODY', $this->oldPublic, $this->oldRoot) ?>
	</p>

	<?php if ($this->isWindows): ?>
	<p>
		<?= AText::_('PUBLICFOLDER_LBL_BUT_WINDOWS') ?>
	</p>
	<?php elseif ($this->isServedDirectly && !$this->isServedFromPublic): ?>
	<p>
		<?= AText::_('PUBLICFOLDER_LBL_BUT_DIRECTLY') ?>
	</p>
	<?php elseif($this->isServedFromPublic && !$this->isServedDirectly): ?>
	<p>
		<?= AText::_('PUBLICFOLDER_LBL_BUT_CUSTOM') ?>
	</p>
	<?php endif ?>

</div>

<div class="akeeba-panel--info" style="<?= $this->hideInterface ? 'display: none;' : '' ?>">
	<header class="akeeba-block-header">
		<h3>
			<?= AText::_('PUBLICFOLDER_LBL_OPTIONS_HEADER') ?>
		</h3>
	</header>

	<form action="index.php" method="post"
		  id="publicfolderForm" name="publicfolderForm"
		  class="akeeba-form--horizontal"
	>
		<?php if ($this->noChoice): ?>
			<input type="hidden" name="usepublic" value="<?= $this->stateVars->usesplit ? '1' : '0' ?>">
		<?php else: ?>
			<div class="akeeba-form-group">
				<label for="usepublic">
					<?php echo AText::_('PUBLICFOLDER_LBL_USE_SPLIT') ?>
				</label>

				<div class="akeeba-toggle">
					<input type="radio" <?php echo !$this->stateVars->usesplit ? 'checked="checked"' : '' ?>
						   name="usepublic" id="usepublic-0" value="0"
					/>
					<label for="usepublic-0" class="red">
						<?php echo AText::_('GENERIC_LBL_NO') ?>
					</label>

					<input type="radio" <?php echo $this->stateVars->usesplit ? 'checked="checked"' : '' ?>
						   name="usepublic" id="usepublic-1" value="1"
					/>
					<label for="usepublic-1" class="green">
						<?php echo AText::_('GENERIC_LBL_YES') ?>
					</label>
				</div>
			</div>
		<?php endif ?>

		<div class="akeeba-form-group">
			<label for="newpublic">
				<?= AText::_('PUBLICFOLDER_LBL_NEWPUBLIC') ?>
			</label>

			<input type="text" name="newpublic" id="newpublic"
				   value="<?= $this->hideInterface ? '' : $this->stateVars->newpublic ?>"
				<?= $this->hideInterface ? 'disabled' : '' ?>
			/>
			<?php if (!$this->hideInterface): ?>
				<p class="akeeba-help-text">
					<?= AText::sprintf('PUBLICFOLDER_LBL_NEWPUBLIC_HELP', $this->escape($this->stateVars->samplefolder)) ?>
				</p>
			<?php endif ?>
		</div>

		<div style="display: none;">
			<input type="hidden" name="view" value="publicfolder" />
			<input type="hidden" name="task" value="apply" />
		</div>

	</form>
</div>