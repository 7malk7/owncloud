<?php
namespace OCA\DeductToDB\Commands;

interface ICommand{
	function execute($app, $mode, $versionFlag);
}


?>