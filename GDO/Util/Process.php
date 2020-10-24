<?php
namespace GDO\Util;

/**
 * Process utilities
 * @author gizmore
 * @since 6.10
 * @version 6.10
 */
final class Process
{
    /**
     * Determines if a command exists on the current environment
     * @param string $command The command to check
     * @return bool True if the command has been found ; otherwise, false.
     * @author https://stackoverflow.com/a/18540185/13599483
     */
    public static function commandExists($command)
    {
        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    
        $pipes = [];
        $process = proc_open(
            "$whereIsCommand $command",
            array(
                0 => array("pipe", "r"), //STDIN
                1 => array("pipe", "w"), //STDOUT
                2 => array("pipe", "w"), //STDERR
            ),
            $pipes
        );
        
        if ($process !== false)
        {
            $stdout = stream_get_contents($pipes[1]);
//             $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            
            return $stdout != '';
        }
        return false;
    }

}