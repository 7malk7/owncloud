<?php
namespace OCA\DeductToDB\Strategies;

interface IStrategy{
	function addCommand( $command );
	function execute($versionFlag);
}


?>