<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

/** Submission Management Constants */
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 2);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 3);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 4);

class TrackSubmissionHandler extends AuthorHandler {
	
	/**
	 * Display list of an author's submissions.
	 */
	function track() {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $authorSubmissionDao->getAuthorSubmissions($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('author/submissions.tpl');
	}
	
	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		parent::validate();
		parent::setupTemplate(true);
			
		$articleId = $args[0];

		TrackSubmissionHandler::validate($articleId);

		$journal = &Request::getJournal();
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$articleDao->deleteArticleById($args[0]);
		
		Request::redirect('author/track');
	}
	
	/**
	 * Display the status and other details of an author's submission.
	 */
	function submission($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$submission = $authorSubmissionDao->getAuthorSubmission($articleId);
			
		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('round', $round);
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'editor.article.decision.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);
	
		$templateMgr->display('author/submission.tpl');
	}
	
	/**
	 * Display the status and other details of an author's submission.
	 */
	function submissionEditing($args) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
	
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$submission = $authorSubmissionDao->getAuthorSubmission($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('copyeditFile', $submission->getCopyeditFile());
		$templateMgr->assign('editorAuthorRevisionFile', $submission->getEditorAuthorRevisionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
	
		$templateMgr->display('author/submissionEditing.tpl');
	}
	
	/**
	 * Upload the author's revised version of an article.
	 */
	function uploadRevisedVersion() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);		
		AuthorAction::uploadRevisedVersion($articleId);
		
		Request::redirect(sprintf('author/submission/%d', $articleId));	
	}
	
	function viewMetadata($args) {
		parent::validate();
		parent::setupTemplate(true);
	
		$articleId = $args[0];
	
		TrackSubmissionHandler::validate($articleId);
		AuthorAction::viewMetadata($articleId, ROLE_ID_AUTHOR);
	}
	
	function saveMetadata() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		AuthorAction::saveMetadata($articleId);
	}

	function uploadCopyeditVersion() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		AuthorAction::uploadCopyeditVersion($articleId);
		
		Request::redirect(sprintf('author/submissionEditing/%d', $articleId));	
	}
	
	function completeAuthorCopyedit($args) {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			AuthorAction::completeAuthorCopyedit($articleId, $send);
			Request::redirect(sprintf('author/submissionEditing/%d', $articleId));
		} else {
			AuthorAction::completeAuthorCopyedit($articleId);
		}
	}
	
	function downloadFile($args) {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = $args[0];
		$fileId = $args[1];
		$revision = isset($args[2]) ? $args[2] : null;
		
		TrackSubmissionHandler::validate($articleId);
		AuthorAction::downloadFile($articleId, $fileId, $revision);
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the author for the article.
	 * Redirects to author index page if validation fails.
	 */
	function validate($articleId) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$isValid = true;
		
		$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($articleId);
	
		if ($authorSubmission == null) {
			$isValid = false;
		} else if ($authorSubmission->getJournalId() != $journal->getJournalId()) {
			$isValid = false;
		} else {
			if ($authorSubmission->getCopyeditorId() != $user->getUserId()) {
				$isValid = false;
			}
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}
	}
}
?>
