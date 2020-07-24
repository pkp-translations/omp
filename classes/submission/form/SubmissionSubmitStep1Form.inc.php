<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep1Form.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep1Form
 * @ingroup submission_form
 *
 * @brief Form for Step 1 of author submission.
 */

import('lib.pkp.classes.submission.form.PKPSubmissionSubmitStep1Form');
import('classes.submission.Submission'); // WORK_TYPE_... constants for form

class SubmissionSubmitStep1Form extends PKPSubmissionSubmitStep1Form {
	/**
	 * Constructor.
	 */
	function __construct($context, $submission = null) {
		parent::__construct($context, $submission);
		$this->addCheck(new FormValidatorCustom($this, 'seriesId', 'optional', 'author.submit.seriesRequired', array(DAORegistry::getDAO('SeriesDAO'), 'getById'), array($context->getId())));
	}

	/**
	 * @copydoc PKPSubmissionSubmitStep1Form::fetch
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);

		// Get series for this context
		$seriesDao = DAORegistry::getDAO('SeriesDAO'); /* @var $seriesDao SeriesDAO */
		$activeSeries = array();
		$seriesIterator = $seriesDao->getByContextId($this->context->getId(), null, !$canSubmitAll);
		while ($series = $seriesIterator->next()) {
			if (!$series->getIsInactive()) {
				$activeSeries[$series->getId()] = $series->getLocalizedTitle();
			}
		}
		$seriesOptions = array('' => __('submission.submit.selectSeries')) + $activeSeries;
		$templateMgr->assign('seriesOptions', $seriesOptions);

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc PKPSubmissionSubmitStep1Form::initData
	 */
	function initData($data = array()) {
		if (isset($this->submission)) {
			parent::initData(array(
				'seriesId' => $this->submission->getSeriesId(),
				'seriesPosition' => $this->submission->getSeriesPosition(),
				'workType' => $this->submission->getWorkType(),
			));
		} else {
			parent::initData();
		}
	}

	/**
	 * Perform additional validation checks
	 * @copydoc PKPSubmissionSubmitStep1Form::validate
	 */
	function validate() {
		if (!parent::validate($callHooks)) return false;

		$request = Application::get()->getRequest();
		$context = $request->getContext();

		// Ensure that submissions are enabled and the assigned series is activated
		$seriesId = $this->getData('seriesId');
		$seriesDao = DAORegistry::getDAO('SeriesDAO'); /* @var $seriesDao SeriesDAO */
		$series = $seriesDao->getById($seriesId);
		if ($context->getData('disableSubmissions') || $series->getIsInactive()) {
			return false;
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'workType', 'seriesId', 'seriesPosition',
		));
		parent::readInputData();
	}

	/**
	 * Set the submission data from the form.
	 * @param $submission Submission
	 */
	function setSubmissionData($submission) {
		$submission->setWorkType($this->getData('workType'));
		$submission->setSeriesId($this->getData('seriesId'));
		$submission->setSeriesPosition($this->getData('seriesPosition'));
		parent::setSubmissionData($submission);
	}
}