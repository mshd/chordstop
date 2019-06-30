<?php
# app/Test.php
namespace App;
use Symfony\Component\Process\ProcessBuilder;
class Test
{
    public static function test()
    {
        $builder = new ProcessBuilder(['ls']);
        $builder->getProcess();
    }
}
