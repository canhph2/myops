<?php

namespace App\Classes;

class MyOpsConsoleArguments
{
    /** @var string|null */
    public $command;

    /** @var string|null */
    public $arg1;

    /** @var string|null */
    public $arg2;

    /** @var string|null */
    public $arg3;

    /** @var string|null */
    public $arg4;

    /** @var array */
    public $argsAll;

    /**
     * @param string|null $command
     * @param string|null $arg1
     * @param string|null $arg2
     * @param string|null $arg3
     * @param string|null $arg4
     * @param array $argsAll
     */
    public function __construct(string $command = null, string $arg1 = null, string $arg2 = null,
                                string $arg3 = null, string $arg4 = null, array $argsAll = [])
    {
        $this->command = $command;
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
        $this->arg3 = $arg3;
        $this->arg4 = $arg4;
        $this->argsAll = $argsAll;
    }

}
