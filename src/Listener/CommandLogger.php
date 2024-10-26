<?php
namespace Vest\Listener;

use Vest\Event\CommandEvent;

class CommandLogger
{
    public function handleCommandEvent(CommandEvent $event)
    {
        $command = $event->getCommand();
        // Registre a execução do comando
        echo "Comando executado: " . $command->getSignature() . PHP_EOL;
    }
}