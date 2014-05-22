<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{

	if (t3lib_extMgm::isLoaded('templavoila'))	{
		require_once (t3lib_extMgm::extPath($_EXTKEY).'class.tx_rlmpofficedocuments_tvmod1.php');
	}

#	t3lib_extMgm::addModule('tools','txrlmpofficedocumentsM1','top',t3lib_extMgm::extPath($_EXTKEY).'mod1/');

	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
		'name' => 'tx_rlmpofficedocuments_cm1',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_rlmpofficedocuments_cm1.php'
	);
}


t3lib_div::loadTCA('tt_content');

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform;;;;1-1-1';

t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:rlmp_officedocuments/flexform_ds.xml');

t3lib_extMgm::addPlugin(Array('LLL:EXT:rlmp_officedocuments/locallang_db.php:tt_content.list_type', $_EXTKEY.'_pi1'),'list_type');

if (t3lib_extMgm::isLoaded('templavoila'))	{
		// Adding datastructure:
	$GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['staticDataStructures'][]=array(
		'title' => 'Documents Suite Template',
		'path' => 'EXT:'.$_EXTKEY.'/template_datastructure.xml',
		'icon' => '',
		'scope' => 0,
	);
}

if (TYPO3_MODE=='BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_rlmpofficedocuments_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_rlmpofficedocuments_pi1_wizicon.php';
}
?>