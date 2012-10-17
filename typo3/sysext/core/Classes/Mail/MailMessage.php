<?php
/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_mail_Message extends Swift_Message {

	/**
	 * @var t3lib_mail_Mailer
	 */
	protected $mailer;

	/**
	 * @var string This will be added as X-Mailer to all outgoing mails
	 */
	protected $mailerHeader = 'TYPO3';

	/**
	 * TRUE if the message has been sent.
	 *
	 * @var boolean
	 */
	protected $sent = FALSE;

	/**
	 * Holds the failed recipients after the message has been sent
	 *
	 * @var array
	 */
	protected $failedRecipients = array();

	/**
	 * @return void
	 */
	private function initializeMailer() {
		$this->mailer = t3lib_div::makeInstance('t3lib_mail_Mailer');
	}

	/**
	 * Sends the message.
	 *
	 * @return integer the number of recipients who were accepted for delivery
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function send() {
		$this->initializeMailer();
		$this->sent = TRUE;
		$this->getHeaders()->addTextHeader('X-Mailer', $this->mailerHeader);
		return $this->mailer->send($this, $this->failedRecipients);
	}

	/**
	 * Checks whether the message has been sent.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isSent() {
		return $this->sent;
	}

	/**
	 * Returns the recipients for which the mail was not accepted for delivery.
	 *
	 * @return array the recipients who were not accepted for delivery
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getFailedRecipients() {
		return $this->failedRecipients;
	}

}

?>