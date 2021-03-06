<?php
namespace BGM\BgmTheme\SignalSlot;

class HreflangTags {

	/**
	 * t3lib_page object for finding rootline on the fly
	 *
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $sysPage;

	/**
	 * @var array
	 */
	protected $currentProduct;

	/**
	 * @param \BGM\BgmHreflang\Utility\HreflangTags $parentObject
	 */
	public function getGetParametersForProducts($parentObject) {
		$getParameters = $parentObject->getGetParameters();
		if (isset($getParameters['Product'])) {
			$this->getCurrentProduct($getParameters['Product']);
			$getParameters['Product'] = array_merge($getParameters['Product'], $this->getRelatedProduct($this->currentProduct, $parentObject->getRelatedPage(), intval($getParameters['L'])));
			$parentObject->setGetParameters($getParameters);
		}
	}

	/**
	 * Gets the full records for the current TreeGroup, Product and ProductVariant
	 *
	 * @param array $product
	 * @return array $currentProduct
	 */
	protected function getCurrentProduct($product) {
		if (!is_array($this->currentProduct)) {
			$currentProduct = array();
			if (intval($product['treeGroup']) > 0) {
				$currentProduct['treeGroup'] = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tx_products_domain_model_treegroup', 'uid=' . intval($product['treeGroup']));
			}
			if (intval($product['product']) > 0) {
				$currentProduct['product'] = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tx_products_domain_model_product', 'uid=' . intval($product['product']));
			}

			$this->currentProduct = $currentProduct;
		}
	}

	/**
	 * Get the TreeGroup and Product in the related page for the current TreeGroup and Product
	 *
	 * @param array $currentProduct
	 * @param integer $relatedPage
	 * @param integer $sysLanguageUid
	 * @return array $products
	 */
	protected function getRelatedProduct($currentProduct, $relatedPage, $sysLanguageUid) {
		$products = array();
		$rootPageId = $this->getRootPageId($relatedPage);

		//We need the products sys_folder pid. in our project, we had this mapping already in the RealURL configuration, so we reuse it here
		$storagePageId = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['development.bgm.projects.localhost']['preVars'][0]['countryMapping'][intval($rootPageId)]['productStorage'];

		if (isset($currentProduct['treeGroup'])) {
			$treeGroup = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid', 'tx_products_domain_model_treegroup', 'ean LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($currentProduct['treeGroup']['ean'], 'tx_products_domain_model_treegroup') . ' AND pid=' . intval($storagePageId) . ' AND sys_language_uid=' . intval($sysLanguageUid) . ' ' . $GLOBALS['TSFE']->sys_page->enableFields('tx_products_domain_model_treegroup'));

			$products['treeGroup'] = intval($treeGroup['uid']);
		}

		if (isset($currentProduct['product'])) {
			$product = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid', 'tx_products_domain_model_product', 'ean LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($currentProduct['product']['ean'], 'tx_products_domain_model_product') . ' AND pid=' . intval($storagePageId) . ' AND sys_language_uid=' . intval($sysLanguageUid) . ' ' . $GLOBALS['TSFE']->sys_page->enableFields('tx_products_domain_model_product'));

			$products['product'] = intval($product['uid']);
		}

		return $products;
	}

	/**
	 * @param integer $pageId
	 * @return integer|mixed
	 */
	protected function getRootPageId($pageId) {
		if (TYPO3_MODE == 'BE') {
			$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pageId);
		} else {
			$this->createSysPageIfNecessary();
			$rootline = $this->sysPage->getRootLine($pageId);
		}
		foreach ($rootline as $rootlinePage) {
			if (intval($rootlinePage['is_siteroot']) == 1) {
				$rootPageId = $rootlinePage['uid'];
				break;
			}
		}

		return $rootPageId;
	}

	/**
	 * Creates $this->sysPage if it does not exist yet.
	 *
	 * @return void
	 */
	protected function createSysPageIfNecessary() {
		if (!is_object($this->sysPage)) {
			$this->sysPage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			$this->sysPage->init($GLOBALS['TSFE']->showHiddenPage || $GLOBALS['TSFE']->beUserLogin);
		}
	}
}

?>