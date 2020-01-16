<?php

namespace App\Command;

use App\Service\ElasticsearchFaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FakeElasticsearchContentCommand extends Command
{
    private $extractionService;

    protected static $defaultName = 'app:fake-elaticsearch-content';

    /**
     * ExtractStatisticsCommand constructor.
     *
     * @param \App\Service\ElasticsearchFaker $elasticsearchFaker
     *   The faker service
     * @param string|null $name
     *   The name of the command; passing null means it must be set in configure()
     */
    public function __construct(ElasticsearchFaker $elasticsearchFaker, string $name = null)
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

        $this->extractionService->createTestData();

        $io->success('Adding fake data to elasticsearch successfully.');

        return 0;
    }
}
