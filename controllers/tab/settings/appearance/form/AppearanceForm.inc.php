<?php

/**
 * @file controllers/tab/settings/appearance/form/AppearanceForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AppearanceForm
 * @ingroup controllers_tab_settings_appearance_form
 *
 * @brief Form to edit appearance settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class AppearanceForm extends ContextSettingsForm {

	/** @var array */
	var $_imagesSettingsName;

	/**
	 * Constructor.
	 */
	function AppearanceForm($wizardMode = false) {
		// Define an array with the image setting name as key and its
		// common alternate text locale key as value.
		$this->setImagesSettingsName(array(
			'homepageImage' => 'common.homepageImage.altText',
			'pageHeaderTitleImage' => 'common.pageHeader.altText',
			'pageHeaderLogoImage' => 'common.pageHeaderLogo.altText'
		));

		$settings = array(
			'pageHeaderTitleType' => 'int',
			'pageHeaderTitle' => 'string',
			'additionalHomeContent' => 'string',
			'pageHeader' => 'string',
			'pageFooter' => 'string',
			'navItems' => 'object',
			'itemsPerPage' => 'int',
			'numPageLinks' => 'int'
		);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/appearance/form/appearanceForm.tpl', $wizardMode);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the images settings name.
	 * @return array
	 */
	function getImagesSettingsName() {
		return $this->_imagesSettingsName;
	}

	/**
	 * Set the image settings name.
	 * @param array $imagesSettingsName
	 * @return array
	 */
	function setImagesSettingsName($imagesSettingsName) {
		$this->_imagesSettingsName = $imagesSettingsName;
	}

	//
	// Implement template methods from Form.
	//
	/**
	 * @see Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array(
			'pageHeaderTitleType',
			'pageHeaderTitle',
			'additionalHomeContent',
			'pageHeader',
			'pageFooter'
		);
	}


	//
	// Extend methods from ContextSettingsForm.
	//
	/**
	 * @see ContextSettingsForm::fetch()
	 */
	function fetch($request) {
		// Get all upload form image link actions.
		$uploadImageLinkActions = array();
		foreach ($this->getImagesSettingsName() as $settingName => $altText) {
			$uploadImageLinkActions[$settingName] = $this->_getFileUploadLinkAction($settingName, 'image', $request);
		}
		// Get the css upload link action.
		$uploadCssLinkAction = $this->_getFileUploadLinkAction('styleSheet', 'css', $request);

		$imagesViews = $this->_renderAllFormImagesViews($request);
		$cssView = $this->renderFileView('styleSheet', $request);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('uploadImageLinkActions', $uploadImageLinkActions);
		$templateMgr->assign('uploadCssLinkAction', $uploadCssLinkAction);

		$params = array(
			'imagesViews' => $imagesViews,
			'styleSheetView' => $cssView,
			'locale' => AppLocale::getLocale()
		);

		return parent::fetch($request, $params);
	}


	//
	// Public methods.
	//
	/**
	 * Render a template to show details about an uploaded file in the form
	 * and a link action to delete it.
	 * @param $fileSettingName string The uploaded file setting name.
	 * @param $request Request
	 * @return string
	 */
	function renderFileView($fileSettingName, $request) {
		$file = $this->getData($fileSettingName);
		$locale = AppLocale::getLocale();

		// Check if the file is localized.
		if (!is_null($file) && key_exists($locale, $file)) {
			// We use the current localized file value.
			$file = $file[$locale];
		}

		// Only render the file view if we have a file.
		if (is_array($file)) {
			$templateMgr = TemplateManager::getManager($request);
			$deleteLinkAction = $this->_getDeleteFileLinkAction($fileSettingName, $request);

			// Get the right template to render the view.
			$imagesSettingsName = $this->getImagesSettingsName();
			if (in_array($fileSettingName, array_keys($imagesSettingsName))) {
				$template = 'controllers/tab/settings/formImageView.tpl';

				// Get the common alternate text for the image.
				$localeKey = $imagesSettingsName[$fileSettingName];
				$commonAltText = __($localeKey);
				$templateMgr->assign('commonAltText', $commonAltText);
			} else {
				$template = 'controllers/tab/settings/formFileView.tpl';
			}

			$templateMgr->assign('file', $file);
			$templateMgr->assign_by_ref('deleteLinkAction', $deleteLinkAction);
			$templateMgr->assign('fileSettingName', $fileSettingName);

			return $templateMgr->fetch($template);
		} else {
			return null;
		}
	}

	/**
	 * Delete an uploaded file.
	 * @param $fileSettingName string
	 * @return boolean
	 */
	function deleteFile($fileSettingName, $request) {
		$context = $request->getContext();
		$locale = AppLocale::getLocale();

		// Get the file.
		$file = $this->getData($fileSettingName);

		// Check if the file is localized.
		if (key_exists($locale, $file)) {
			// We use the current localized file value.
			$file = $file[$locale];
		} else {
			$locale = null;
		}

		// Deletes the file and its settings.
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->removeContextFile($context->getAssocType(), $context->getId(), $file['uploadName'])) {
			$settingsDao = $context->getSettingsDao();
			$settingsDao->deleteSetting($context->getId(), $fileSettingName, $locale);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @see ContextSettingsForm::execute()
	 */
	function execute($request) {
		parent::execute($request);

		// Save block plugins context positions.
		import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
		ListbuilderHandler::unpack($request, $request->getUserVar('blocks'));
	}

	/**
	 * Overriden method from ListbuilderHandler.
	 * @param $request Request
	 * @param $rowId mixed
	 * @param $newRowId array
	 */
	function updateEntry($request, $rowId, $newRowId) {
		$plugins =& PluginRegistry::loadCategory('blocks');
		$plugin =& $plugins[$rowId]; // Ref hack
		switch ($newRowId['listId']) {
			case 'unselected':
				$plugin->setEnabled(false);
				break;
			case 'leftContext':
				$plugin->setEnabled(true);
				$plugin->setBlockContext(BLOCK_CONTEXT_LEFT_SIDEBAR);
				$plugin->setSeq((int) $newRowId['sequence']);
				break;
			case 'rightContext':
				$plugin->setEnabled(true);
				$plugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
				$plugin->setSeq((int) $newRowId['sequence']);
				break;
			default:
				assert(false);
		}
	}

	/**
	 * Avoid warnings when Listbuilder::unpack tries to call this method.
	 */
	function deleteEntry() {
		return false;
	}

	/**
	 * Avoid warnings when Listbuilder::unpack tries to call this method.
	 */
	function insertEntry() {
		return false;
	}


	//
	// Private helper methods
	//
	/**
	 * Render all form images views.
	 * @param $request Request
	 * @return array
	 */
	function _renderAllFormImagesViews($request) {
		$imagesViews = array();
		foreach ($this->getImagesSettingsName() as $imageSettingName => $altText) {
			$imagesViews[$imageSettingName] = $this->renderFileView($imageSettingName, $request);
		}

		return $imagesViews;
	}

	/**
	 * Get a link action for file upload.
	 * @param $settingName string
	 * @param $fileType string The uploaded file type.
	 * @param $request Request
	 * @return LinkAction
	 */
	function &_getFileUploadLinkAction($settingName, $fileType, $request) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$ajaxModal = new AjaxModal(
			$router->url(
				$request, null, null, 'showFileUploadForm', null, array(
					'fileSettingName' => $settingName,
					'fileType' => $fileType
				)
			),
			__('common.upload'),
			'modal_add_file'
		);
		import('lib.pkp.classes.linkAction.LinkAction');
		$linkAction = new LinkAction(
			'uploadFile-' . $settingName,
			$ajaxModal,
			__('common.upload'),
			'add'
		);

		return $linkAction;
	}

	/**
	 * Get the delete file link action.
	 * @param $setttingName string File setting name.
	 * @param $request Request
	 * @return LinkAction
	 */
	function &_getDeleteFileLinkAction($settingName, $request) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

		$confirmationModal = new RemoteActionConfirmationModal(
			__('common.confirmDelete'), null,
			$router->url(
				$request, null, null, 'deleteFile', null, array(
					'fileSettingName' => $settingName,
					'tab' => 'appearance'
				)
			),
			'modal_delete'
		);
		$linkAction = new LinkAction(
			'deleteFile-' . $settingName,
			$confirmationModal,
			__('common.delete'),
			null
		);

		return $linkAction;
	}
}

?>
