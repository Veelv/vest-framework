<?php
namespace Vest\Console;
class ListCommand extends Command {
    protected $name = 'list';
    protected $description = 'Lista todos os comandos disponíveis';

    public function execute(array $args): void {
        echo "Comandos disponíveis:\n\n";
        
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