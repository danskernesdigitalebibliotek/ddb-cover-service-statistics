<?php

/**
 * @file
 * Contains a command to extract statistics from Elasticsearch.
 */

namespace App\Command;

use App\Model\CsvTarget;
use App\Service\StatisticsExtractionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ExtractStatisticsForDayCommand.
 */
class ExtractStatisticsSingleDayCommand extends Command
{
    private $extractionService;
    private $dateFormat = 'd-m-Y';

    protected static $defaultName = 'app:extract-statistics-day';

    /**
     * ExtractStatisticsCommand constructor.
     *
     * @param StatisticsExtractionService $extractionService
     *   The extraction service
     * @param string|null $name
     *   The name of the command; passing null means it must be set in configure()
     */
    public function __construct(StatisticsExtractionService $extractionService, string $name = null)
    {
        $this->extractionService = $extractionService;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Extracts statistics for a single day');

        $this->addArgument(
            'date',
            InputArgument::REQUIRED,
            sprintf('Processes completed before this date will be deleted. Format: %s', $this->dateFormat),
            null
        );

        $this->addArgument(
            'filename',
            InputArgument::OPTIONAL,
            'Csv filename to extract to.',
            null
        );
    }

    /**
     * {@inheritdoc}
     *
     * @suppress PhanUndeclaredMethod
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rawDate = $input->getArgument('date');

        $dayToExtract = \DateTime::createFromFormat($this->dateFormat, $rawDate);

        if (!$dayToExtract) {
            throw new \RuntimeException('Invalid date format for date argument.');
        }

        $progressBarSheet = new ProgressBar($output);
        $progressBarSheet->setFormat('[%bar%] %elapsed% (%memory%) - %message%');
        $this->extractionService->setProgressBar($progressBarSheet);

        $now = new \DateTime();

        if ($input->hasArgument('filename')) {
            $filename = $input->getArgument('filename');
        }
        else {
            $filename = $dayToExtract->format('d-m-Y').'_extracted-at-'.$now->format('d-m-Y_H:i').'.csv';
        }

        $target = new CsvTarget($filename);

        $this->extractionService->extractStatisticsForDay($dayToExtract, $target);

        $io->success('Data extracted successfully to file: '.$filename);

        return 0;
    }
}
