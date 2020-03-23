<?php

/**
 * @file
 * Contains a command to remove already extracted entries.
 */

namespace App\Command;

use App\Service\StatisticsExtractionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CleanupEntriesCommand.
 */
class CleanupEntriesCommand extends Command
{
    protected static $defaultName = 'app:cleanup:entries';

    private $extractionService;

    /**
     * CleanupEntriesCommand constructor.
     *
     * @param StatisticsExtractionService $extractionService
     *   The statistics extraction service
     */
    public function __construct(StatisticsExtractionService $extractionService)
    {
        $this->extractionService = $extractionService;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Remove already extracted entries')
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days since extraction')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = $input->getArgument('days');
        $compareDate = is_numeric($days) ? new \DateTime('-'.$days.' days') : new \DateTime();
        $this->extractionService->removeExtractedEntries($compareDate);

        $io->success('Extracted entries removed');

        return 0;
    }
}
