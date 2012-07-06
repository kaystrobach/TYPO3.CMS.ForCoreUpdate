<?php

class Typo3_Backend implements t3lib_Singleton {
	/**
	 * Debug Mode
	 */
	protected $debug = FALSE;

	protected $content = '';

	protected $templatePath;

	/**
	 * Object for loading backend modules
	 *
	 * @var t3lib_loadModules
	 */
	protected $moduleLoader;

	/**
	 * module menu generating object
	 *
	 * @var ModuleMenu
	 */
	protected $moduleMenu;

	/**
	 * Pagerenderer
	 *
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	public function __construct() {
		$this->templatePath = PATH_typo3 . 'templates/BackendNeuFluid.html';

			// Set debug flag for BE development only
		$this->debug = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) === 1;

			// Initializes the backend modules structure for use later.
		$this->moduleLoader = t3lib_div::makeInstance('t3lib_loadModules');
		$this->moduleLoader->load($GLOBALS['TBE_MODULES']);

		$this->moduleMenu = t3lib_div::makeInstance('ModuleMenu');

		$this->pageRenderer = $GLOBALS['TBE_TEMPLATE']->getPageRenderer();
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();

		$this->pageRenderer->addJsInlineCode(
			'consoleOverrideWithDebugPanel',
			'//already done',
			FALSE
		);
		$this->pageRenderer->addExtDirectCode();

			// Add default BE javascript
		$this->js = '';
		$this->jsFiles = array(
			'common'                => 'js/common.js',
			//'locallang'             => $this->getLocalLangFileName(),
			'modernizr'             => 'contrib/modernizr/modernizr.min.js',
			'swfupload'             => 'contrib/swfupload/swfupload.js',
			'swfupload.swfobject'   => 'contrib/swfupload/plugins/swfupload.swfobject.js',
			'swfupload.cookies'     => 'contrib/swfupload/plugins/swfupload.cookies.js',
			'swfupload.queue'       => 'contrib/swfupload/plugins/swfupload.queue.js',
			'md5'                   => 'md5.js',
			'toolbarmanager'        => 'js/toolbarmanager.js',
			'modulemenu'            => 'js/modulemenu.js',
			'iecompatibility'       => 'js/iecompatibility.js',
			'flashupload'           => 'js/flashupload.js',
			'evalfield'             => '../t3lib/jsfunc.evalfield.js',
			'flashmessages'         => '../t3lib/js/extjs/ux/flashmessages.js',
			'tabclosemenu'          => '../t3lib/js/extjs/ux/ext.ux.tabclosemenu.js',
			'notifications'         => '../t3lib/js/extjs/notifications.js',
			'backend'               => 'js/backend.js',
			'loginrefresh'          => 'js/loginrefresh.js',
			'debugPanel'            => 'js/extjs/debugPanel.js',
			'viewport'              => 'js/extjs/viewport.js',
			'iframepanel'           => 'js/extjs/iframepanel.js',
			'backendcontentiframe'  => 'js/extjs/backendcontentiframe.js',
			'modulepanel'           => 'js/extjs/modulepanel.js',
			'viewportConfiguration' => 'js/extjs/viewportConfiguration.js',
			'util'					=> '../t3lib/js/extjs/util.js',
		);

		$this->loadLanguageFiles();
	}

	protected function loadLanguageFiles() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xml');
	}

	public function render() {


		/** @var Tx_Fluid_View_StandaloneView $fluidRenderer */
		$fluidRenderer = t3lib_div::makeInstance('Tx_Fluid_View_StandaloneView');
		$fluidRenderer->setTemplatePathAndFilename($this->templatePath);
		$fluidRenderer->setPartialRootPath(PATH_typo3 . 'templates' . DIRECTORY_SEPARATOR . 'Partials');

		$fluidRenderer->assign('logo', $this->getBackendLogo()->render());
		$fluidRenderer->assign('logoutButton', $this->moduleMenu->renderLogoutButton());
		$fluidRenderer->assign('BackendUser', $GLOBALS['BE_USER']);
		$fluidRenderer->assign('ModuleMenu', t3lib_div::makeInstance('Typo3_ModuleStorage')->getEntries());

		$this->pageRenderer->addExtOnReadyCode("
			new Ext.Container({
				id: 'typo3-contentContainerWrapper',
				applyTo: '#typo3-module-container',
				border: false,
				layout: 'fit',
				name: 'content',
				layout: 'card',
				activeItem: 0,
				items: TYPO3.Viewport.ContentCards.cards
			});

		");

		$this->content = $fluidRenderer->render();
		$this->content = $GLOBALS['TBE_TEMPLATE']->render(
					$this->getDocumentTitle(),
					$this->content
		);


		$hookConfiguration = array('content' => &$this->content);
		$this->executeHook('renderPostProcess', $hookConfiguration);

		echo $this->content;
	}

	public function addToolbarItem($toolbarItemName, $toolbarItemClassName) {

	}

	public function addCssFile($cssFileName, $cssFile) {

	}

	public function addCss($css) {

	}

	public function addJavascriptFile($javascriptFile) {

	}

	public function addJavascript($javascript) {

	}

	protected function getDocumentTitle() {
		$title = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
			? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ' [TYPO3 '.TYPO3_version.']'
			: 'TYPO3 ' . TYPO3_version
		);

		return $title;
	}

	/**
	 * Executes defined hooks functions for the given identifier.
	 *
	 * These hook identifiers are valid:
	 *	+ constructPostProcess
	 *	+ renderPreProcess
	 *	+ renderPostProcess
	 *
	 * @param string $identifier Specific hook identifier
	 * @param array $hookConfiguration Additional configuration passed to hook functions
	 * @return void
	 */
	protected function executeHook($identifier, array $hookConfiguration = array()) {
		$options =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php'];

		if (isset($options[$identifier]) && is_array($options[$identifier])) {
			foreach($options[$identifier] as $hookFunction) {
				t3lib_div::callUserFunction($hookFunction, $hookConfiguration, $this);
			}
		}
	}

	protected function getBackendLogo() {
			// Prepare the scaffolding, at this point extension may still add javascript and css
		$logo = t3lib_div::makeInstance('TYPO3Logo');
		$logo->setLogo('gfx/typo3logo_mini.png');

		return $logo;
	}

}

class_alias('TYPO3_Backend', 'TYPO3backend');

?>