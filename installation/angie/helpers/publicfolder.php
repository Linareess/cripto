<?php
/**
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Helper methods to work with the site's public folder
 *
 * This is necessary because Joomla 5.0 and later might set up a custom public folder inside the web root, with Joomla
 * itself being installed very likely completely outside the web root.
 *
 * @since 9.8.1
 */
abstract class AngieHelperPublicfolder
{
	/**
	 * Set up the custom public folder information in session
	 *
	 * @internal
	 * @return  void
	 * @throws  AExceptionApp
	 * @since   9.8.1
	 */
	public static function setUpInSession()
	{
		// Store the public folder information in the session
		/** @var AngieModelBaseMain $model */
		$model           = AModel::getTmpInstance('Main', 'AngieModel');
		$extraInfo       = $model->getExtraInfoObject();
		$hasCustomPublic = isset($extraInfo->custom_public) && $extraInfo->custom_public
		                   && isset($extraInfo->JPATH_PUBLIC)
		                   && !empty($extraInfo->JPATH_PUBLIC);
		$hasOldRoot      = isset($extraInfo->JPATH_ROOT) && !empty($extraInfo->JPATH_ROOT);
		$publicFolder    = $hasCustomPublic ? $extraInfo->JPATH_PUBLIC : APATH_ROOT;
		$oldRoot         = $hasOldRoot ? $extraInfo->JPATH_ROOT : $extraInfo->root;

		$container = AApplication::getInstance()->getContainer();
		$session   = $container->session;
		$session->set('joomla.custom_public_folder', $hasCustomPublic);
		$session->set('joomla.old_public_folder', $publicFolder);
		$session->set('joomla.old_root', $oldRoot);
	}

	/**
	 * Do we have a custom public folder?
	 *
	 * Note that if true it could be anywhere, i.e. inside or outside APATH_ROOT.
	 *
	 * @return  bool
	 * @throws  AExceptionApp
	 * @since   9.8.1
	 */
	public static function hasCustomPublic()
	{
		$session = AApplication::getInstance()->getContainer()->session;

		return (bool) $session->get('joomla.custom_public_folder', false);
	}

	/**
	 * What is the real public folder of the site?
	 *
	 * @return  bool|string
	 * @throws  AExceptionApp
	 * @since   9.8.1
	 */
	public static function getPublicFolder()
	{
		if (!self::hasCustomPublic())
		{
			return APATH_SITE;
		}

		$session = AApplication::getInstance()->getContainer()->session;

		return $session->get('joomla.public_folder', APATH_SITE);
	}

	/**
	 * Set the real public folder of the site.
	 *
	 * This should only be called when restoring the add-on directory with key `JPATH_PUBLIC`.
	 *
	 * @param   string  $newPublicFolder
	 *
	 * @return  void
	 * @throws  AExceptionApp
	 * @since   9.8.1
	 */
	public static function setPublicFolder($newPublicFolder)
	{
		$session = AApplication::getInstance()->getContainer()->session;

		$session->set('joomla.public_folder', $newPublicFolder);
	}

	/**
	 * Get the old public folder (the one set at backup time). NULL if this information is not provided.
	 *
	 * @return  string|null
	 * @throws  AExceptionApp
	 * @since   9.8.1
	 */
	public static function getOldPublicFolder()
	{
		if (!self::hasCustomPublic())
		{
			return null;
		}

		$session = AApplication::getInstance()->getContainer()->session;

		return $session->get('joomla.old_public_folder', null);
	}

	/**
	 * Get the old site root (where the Joomla installation was at backup time). NULL if we don't know.
	 *
	 * @return  string|null
	 * @throws  AExceptionApp
	 * @since   9.8.1
	 */
	public static function getOldSiteRoot()
	{
		$session = AApplication::getInstance()->getContainer()->session;

		return $session->get('joomla.old_root', null);
	}
}