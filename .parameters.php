<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Highloadblock as HL;

Loader::includeModule("highloadblock");

$arHighLoadBlocks = array();
$arColumns = array(
	"PHONE" => GetMessage("SMS_PHONE"),
    "ACTIVATE" => GetMessage("SMS_ACTIVATE"),
    "STATUS" => GetMessage("SMS_STATUS"),
    "STOCK" => GetMessage("SMS_STOCK"),
    "NOVELTIES" => GetMessage("SMS_NOVELTIES"),
    "AVAILABILITY" => GetMessage("SMS_AVAILABILITY"),
    "DELETE" => GetMessage("SMS_DELETE"),
);

$resHLBT = HLBT::getList(array(
    'select' => array('*', 'NAME_LANG' => 'LANG.NAME'),
    'order' => array('NAME_LANG' => 'ASC', 'NAME' => 'ASC')
));
while ($arHLBT = $resHLBT->fetch()) {
    $arHighLoadBlocks[$arHLBT['ID']] = $arHLBT['NAME_LANG'];
}

$arComponentParameters = array(
    "PARAMETERS" => array(
        "HIGHLOAD_BLOCK_ID" => array(
            "NAME" => GetMessage("SMS_HIGHLOAD_BLOCKS"),
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "ADDITIONAL_VALUES" => "N",
            "VALUES" => $arHighLoadBlocks,
            "PARENT" => "BASE",
        ),
		"MAX_COUNT_PHONES" => array(
			"NAME" => GetMessage('SMS_MAX_COUNT_PHONES'),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "10",
			"ADDITIONAL_VALUES" => "N",
			"PARENT" => "BASE",
		),
		"SHOW_ADD_PHONE" => array(
			"NAME" => GetMessage("SMS_SHOW_ADD_PHONE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"PARENT" => "BASE",
		),
        "COLUMNS_LIST_EXT" => array(
            "NAME" => GetMessage("SMS_COLUMNS_LIST"),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arColumns,
            "DEFAULT" => array('ACTIVATE', 'STATUS', 'STOCK', 'NOVELTIES', 'AVAILABILITY', 'DELETE'),
            "COLS" => 25,
            "SIZE" => 7,
            "ADDITIONAL_VALUES" => "N",
            "PARENT" => "VISUAL",
        ),
        "MAIN_CHAIN_NAME" => array(
            "NAME" => GetMessage('SMS_MAIN_CHAIN_NAME'),
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "",
            "ADDITIONAL_VALUES" => "N",
            "PARENT" => "ADDITIONAL_SETTINGS",
        ),
        "SET_TITLE" => array(
            "NAME" => GetMessage("SMS_SET_TITLE"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
            "PARENT" => "ADDITIONAL_SETTINGS",
        ),
        "CACHE_TIME" => array("DEFAULT" => 3600),
        "CACHE_GROUPS" => array(
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => GetMessage("SMS_CACHE_GROUPS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
    )
);
