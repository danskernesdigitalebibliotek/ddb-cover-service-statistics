<?php

/**
 * @file
 * Contains a command to create fake content in Elasticsearch.
 */

namespace App\Command;

use App\Test\DataFakerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class FakeElasticsearchContentCommand.
 */
class FakeCommand extends Command
{
    private $fakerService;

    protected static $defaultName = 'app:fake-content';

    /**
     * ExtractStatisticsCommand constructor.
     *
     * @param \App\Service\DataFakerService $extractionService
     *   The faker service
     * @param string|null $name
     *   The name of the command; passing null means it must be set in configure()
     */
    public function __construct(DataFakerService $extractionService, string $name = null)
    {
        $this->fakerService = $extractionService;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add fake data to elasticsearch');
        $this->addArgument('date', InputArgument::OPTIONAL, 'Day to add entries to');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dateString = $input->getArgument('date');

        if (null === $dateString) {
            $io->warning('This will create fake content in elasticsearch. If you want to continue, enter a date below.');

            $dateString = $io->ask('Select a date (for example "7 december 2019" or "-2 days"). Defaults to today.', 'today');
        }

        $date = new \DateTime($dateString);

        $this->fakerService->createElasticsearchTestData($date);

        $io->success('Adding fake data to elasticsearch successfully.');

        return 0;
    }
}
