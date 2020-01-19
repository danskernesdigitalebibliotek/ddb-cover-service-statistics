<?php

namespace App\Command;

use App\Service\StatisticsExtractionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanupEntriesCommand extends Command
{
    protected static $defaultName = 'app:cleanup-entries';

    private $extractionService;

    /**
     * CleanupEntriesCommand constructor.
     *
     * @param \App\Service\StatisticsExtractionService $extractionService
     *   The statistics extration service
     */
    public function __construct(StatisticsExtractionService $extractionService)
    {
        $this->extractionService = $extractionService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Remove already extracted entries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->extractionService->removeExtractedEntries();

        $io->success('Extracted entries removed');

        return 0;
    }
}
