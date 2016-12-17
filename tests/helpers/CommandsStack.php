<?php

namespace Adeira\Tests;

class CommandsStack
{

	public $commands;

	public function addCommands(array $commands)
	{
		$this->commands = $commands;
	}

}
