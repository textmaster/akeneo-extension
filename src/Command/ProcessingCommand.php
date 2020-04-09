<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessingCommand.
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class ProcessingCommand extends Command
{
    use LockableTrait;
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:processing')
            ->setDescription('Run all command to manage project in textmaster');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!$this->lock()) {
            $this->writeMessage(
                sprintf('The command "%s" is still running in another process.', self::$defaultName)
            );

            exit(0);
        }

        try {
            $this->writeMessage('Start command pim:textmaster:create-project');
            $this->runCommand($input, 'pim:textmaster:create-project');

            $this->writeMessage('---------------------------------------------');
            $this->writeMessage('Start command pim:textmaster:finalize-project');
            $this->runCommand($input, 'pim:textmaster:finalize-project');

            $this->writeMessage('---------------------------------------------');
            $this->writeMessage('Start command pim:textmaster:update-products');
            $this->runCommand($input, 'pim:textmaster:update-products');
        } catch (Exception $exception) {
            $this->writeMessage($exception->getMessage());
            exit(1);
        }

        $this->release();

        exit(0);
    }
}
