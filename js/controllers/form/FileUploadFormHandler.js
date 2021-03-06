/**
 * @file js/controllers/form/FileUploadFormHandler.js
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileUploadFormHandler
 * @ingroup js_controllers_form
 *
 * @brief File upload form handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form The wrapped HTML form element.
	 * @param {{
	 *  resetUploader: boolean,
	 *  $uploader: jQueryObject,
	 *  uploaderOptions: Object
	 *  }} options Form validation options.
	 */
	$.pkp.controllers.form.FileUploadFormHandler =
			function($form, options) {

		this.parent($form, options);

		if (options.resetUploader !== undefined) {
			this.resetUploader_ = options.resetUploader;
		}

		// Attach the uploader handler to the uploader HTML element.
		this.attachUploader_(options.$uploader, options.uploaderOptions);

		this.uploaderSetup(options.$uploader);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.FileUploadFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	/**
	 * Reset the uploader widget flag.
	 * @private
	 * @type {boolean}
	 */
	$.pkp.controllers.form.FileUploadFormHandler.prototype.
			resetUploader_ = false;


	//
	// Extended methods from AjaxFormHandler.
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.form.FileUploadFormHandler.prototype.handleResponse =
			function(formElement, jsonData) {

		var fileUploader;

		if (this.resetUploader_) {
			fileUploader = $('#plupload', this.getHtmlElement())
					.plupload('getUploader');
			fileUploader.splice();
			fileUploader.refresh();

			// Reset the temporary file id value.
			$('#temporaryFileId', this.getHtmlElement()).val('');
		}

		return /** @type {boolean} */ (
				this.parent('handleResponse', formElement, jsonData));
	};


	//
	// Public methods
	//
	/**
	 * The setup callback of the uploader.
	 * @param {jQueryObject} $uploader Element that contains the plupload object.
	 */
	$.pkp.controllers.form.FileUploadFormHandler.prototype.
			uploaderSetup = function($uploader) {

		var pluploader = $uploader.plupload('getUploader');

		// Subscribe to uploader events.
		pluploader.bind('FileUploaded',
				this.callbackWrapper(this.handleUploadResponse));
	};


	/**
	 * Handle the response of a "file upload" request.
	 * @param {Object} caller The original context in which the callback was called.
	 * @param {Object} pluploader The pluploader object.
	 * @param {Object} file The data of the uploaded file.
	 * @param {{response: string}} ret The serialized JSON response.
	 */
	$.pkp.controllers.form.FileUploadFormHandler.prototype.
			handleUploadResponse = function(caller, pluploader, file, ret) {

		// Handle the server's JSON response.
		var jsonData = /** @type {boolean|{uploadedFile: Object,
				  temporaryFileId: string, content: string}} */
				(this.handleJson($.parseJSON(ret.response))),
				$uploadForm, $temporaryFileId;
		if (jsonData !== false) {
			// Trigger the file uploaded event.
			this.trigger('fileUploaded', [jsonData.uploadedFile]);

			if (jsonData.content === '') {
				// Successful upload to temporary file; save to main form.
				$uploadForm = this.getHtmlElement();
				$temporaryFileId = $uploadForm.find('#temporaryFileId');
				$temporaryFileId.val(jsonData.temporaryFileId);
			} else {
				// Display the revision confirmation form.
				this.getHtmlElement().replaceWith(jsonData.content);
			}
		}
	};


	//
	// Private methods
	//
	/**
	 * Attach the uploader handler.
	 * @private
	 * @param {jQueryObject} $uploader The wrapped HTML uploader element.
	 * @param {Object} options Uploader options.
	 */
	$.pkp.controllers.form.FileUploadFormHandler.prototype.
			attachUploader_ = function($uploader, options) {

		// Attach the uploader handler to the uploader div.
		$uploader.pkpHandler('$.pkp.controllers.UploaderHandler', options);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
