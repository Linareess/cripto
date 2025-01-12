<?php
/**
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

class AngieControllerJoomlaPublicfolder extends AngieControllerBaseMain
{
	public function apply()
	{
		/** @var AngieModelJoomlaPublicfolder $model */
		$model = $this->getThisModel();
		$msg   = null;
		$type  = null;

		try
		{
			$model->checkSettings();
			$model->deleteFilesOnRevertingPublicFolder();
			$model->moveBasicFiles();
			$model->recursiveMove();
			$model->autoEditFiles();
			$model->removeExternalFilesContainerFolder();
			// TODO Conditionally delete the JPATH_PUBLIC key in #__akeeba_common

			$usePublic = (bool) $model->getState('usepublic', true);
			$newPublic = $model->getState('newpublic');
			$this->container->session->set('joomla.public_folder', $usePublic ? $newPublic : APATH_ROOT);

			$stepsModel = AModel::getAnInstance('Steps', 'AngieModel');
			$nextStep   = $stepsModel->getNextStep();

			$url = 'index.php?view=' . $nextStep['step'];
		}
		catch (Exception $exc)
		{
			$type = 'error';
			$msg  = $exc->getMessage();
			$url  = 'index.php?view=publicfolder';
		}

		$this->setRedirect($url, $msg, $type);

		// Encode the result if we're in JSON format
		if ($this->input->getCmd('format', '') == 'json')
		{
			$result['error'] = $msg;

			@ob_clean();
			echo json_encode($result);
		}
	}
}