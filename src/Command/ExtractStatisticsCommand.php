<?php

namespace App\Command;

use App\Service\StatisticsExtractionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExtractStatisticsCommand extends Command
{
    private $extractionService;

    protected static $defaultName = 'app:extract-statistics';

    /**
     * ExtractStatisticsCommand constructor.
     *
     * @param \App\Service\StatisticsExtractionService $elasticsearchFaker
     * @param string|null $name               The name of the command; passing null means it must be set in configure()
     */
    public function __construct(StatisticsExtractionService $elasticsearchFaker, string $name = null)
    {
        $this->extractionService = $elasticsearchFaker;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Extracts statistics');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->extractionService->getLatestData();

        $io->success('Data extracted successfully.');

        return 0;
    }
}
