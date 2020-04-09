<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait CommandTrait
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 */
trait CommandTrait
{

    /** @var array */
    protected $apiTemplates;

    /** @var OutputInterface */
    protected $output;

    /**
     * Run Command method.
     *
     * @param InputInterface $input
     * @param string         $command
     * @param array          $arguments
     */
    protected function runCommand(InputInterface $input, string $command, array $arguments = []): void
    {
        $command   = $this->getApplication()->find($command);

        $input = new ArrayInput(array_merge(['--env' => $input->getOption('env')], $arguments));
        $command->run($input, $this->output);
    }

    /**
     * @param string $message
     */
    protected function writeMessage($message): void
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }
}
