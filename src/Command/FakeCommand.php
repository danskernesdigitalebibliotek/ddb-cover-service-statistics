<?php

/**
 * @file
 * Contains a command to create fake content in Elasticsearch.
 */

namespace App\Command;

use App\Service\DataFakerService;
use Symfony\Component\Console\Command\Command;
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
     * @param \App\Service\DataFakerService $elasticsearchFaker
     *   The faker service
     * @param string|null $name
     *   The name of the command; passing null means it must be set in configure()
     */
    public function __construct(DataFakerService $elasticsearchFaker, string $name = null)
    {
        $this->fakerService = $elasticsearchFaker;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add fake data to elasticsearch');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $date = $io->ask('Select a date (for example "7 december 2019" or "-2 days")?', 'today');

        $date = new \DateTime($date);

        $this->fakerService->createElasticsearchTestData($date);

        $io->success('Adding fake data to elasticsearch successfully.');

        return 0;
    }
}
