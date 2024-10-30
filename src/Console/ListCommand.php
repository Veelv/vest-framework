<?php
namespace Vest\Console;
class ListCommand extends Command {
    public function execute(array $args): void {
        echo "Available commands:\n\n";
        
        $collection = new CommandCollection();
        
        // Adiciona todos os comandos à coleção
        $commands = [
            new ListCommand(),
            new MakeCommand(),
            new MigrationCommand(),
            new SeedCommand(),
            new FrontendCommand()
        ];
        
        foreach ($commands as $command) {
            $collection->add($command);
        }
        
        // Lista todos os comandos
        foreach ($collection->all() as $command) {
            printf("%-15s %s\n", 
                $command->getName(), 
                $command->getDescription()
            );
        }
    }
}