<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_rlmpofficedocuments_pi1.php','_pi1','list_type',1);

if (t3lib_extMgm::isLoaded('templavoila'))	{
	$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContentClass'][] = 'tx_rlmpofficedocuments_tvmod1';
}

?>