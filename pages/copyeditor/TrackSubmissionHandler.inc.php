<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.copyeditor
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class TrackSubmissionHandler extends CopyeditorHandler {
	
	function assignments($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('copyeditor/submissions.tpl');
	}
	
	function submission($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = $args[0];
		
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$submission = $copyeditorSubmissionDao->getCopyeditorSubmission($articleId);

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('initialRevisionFile', $submission->getInitialRevisionFile());
		$templateMgr->assign('finalRevisionFile', $submission->getFinalRevisionFile());
		
		$templateMgr->display('copyeditor/submission.tpl');
	}
	
	function completeCopyedit($args) {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			CopyeditorAction::completeCopyedit($articleId, $send);
			Request::redirect(sprintf('copyeditor/submission/%d', $articleId));
		} else {
			CopyeditorAction::completeCopyedit($articleId);
		}
	}
	
	function completeFinalCopyedit($args) {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			CopyeditorAction::completeFinalCopyedit($articleId, $send);
			Request::redirect(sprintf('copyeditor/submission/%d', $articleId));
		} else {
			CopyeditorAction::completeFinalCopyedit($articleId);
		}
	}
	
	function uploadCopyeditVersion() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		CopyeditorAction::uploadCopyeditVersion($articleId);
		
		Request::redirect(sprintf('copyeditor/submission/%d', $articleId));	
	}
	
	function downloadFile($args) {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = $args[0];
		$fileId = $args[1];
		$revision = isset($args[2]) ? $args[2] : null;
		
		TrackSubmissionHandler::validate($articleId);
		CopyeditorAction::downloadFile($articleId, $fileId, $revision);
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the assigned copyeditor for
	 * the article.
	 * Redirects to copyeditor index page if validation fails.
	 */
	function validate($articleId) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$isValid = true;
		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId, $user->getUserId());
		
		if ($copyeditorSubmission == null) {
			$isValid = false;
		} else if ($copyeditorSubmission->getJournalId() != $journal->getJournalId()) {
			$isValid = false;
		} else {
			if ($copyeditorSubmission->getCopyeditorId() != $user->getUserId()) {
				$isValid = false;
			}
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}
	}
}
?>
