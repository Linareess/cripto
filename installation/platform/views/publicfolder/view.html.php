<?php
/**
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die();

class AngieViewPublicfolder extends AView
{
	protected $oldPublic;

	protected $oldRoot;

	protected $stateVars;

	protected $isWindows;

	protected $isServedDirectly;

	protected $isServedFromPublic;

	protected $noChoice;

	protected $hideInterface;

	/**
	 * @return bool
	 */
	public function onBeforeMain()
	{
		/** @var AngieModelJoomlaPublicfolder $model */
		$model           = $this->getModel();
		$info            = $model->getStoredInfo();
		$this->oldPublic = $info['oldPublic'] ?: '???';
		$this->oldRoot   = $info['oldRoot'] ?: '???';

		$this->isWindows          = substr(strtolower(PHP_OS), 0, 3) === 'win';
		$this->isServedDirectly   = $model->isServedDirectly();
		$this->isServedFromPublic = $model->isServedFromPublic();
		$this->hideInterface      = $this->isWindows
		                            || ($this->isServedDirectly && !$this->isServedFromPublic);
		$this->noChoice           = $this->isWindows
		                            || ($this->isServedDirectly && !$this->isServedFromPublic)
		                            || ($this->isServedFromPublic && !$this->isServedDirectly);

		$usesplit = !$this->isWindows;

		if ($this->isServedDirectly && !$this->isServedFromPublic)
		{
			$usesplit = false;
		}
		elseif ($this->isServedFromPublic && !$this->isServedDirectly && !$this->isWindows)
		{
			$usesplit = true;
		}

		$this->stateVars = (object) [
			'usesplit'     => $usesplit,
			'newpublic'    => $model->getState('newpublic', $model->getDefaultPublicFolder()),
			'samplefolder' => $model->getExamplePublicRoot(),
		];

		$this->loadHelper('select');

		return true;
	}
}
