<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("SMS_NAME"),
	"DESCRIPTION" => GetMessage("SMS_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"PATH" => array(
		"ID" => "e-store",
		"CHILD" => array(
			"ID" => "sale_personal",
			"NAME" => GetMessage("SMS_MAIN")
		)
	),
);
?>