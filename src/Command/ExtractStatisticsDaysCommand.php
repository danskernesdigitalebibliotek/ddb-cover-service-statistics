<?php

/**
 * @file
 * Contains a command to extract statistics from Elasticsearch.
 */

namespace App\Command;

use App\Export\CsvTarget;
use App\Service\StatisticsExtractionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ExtractStatisticsForDayCommand.
 */
class ExtractStatisticsDaysCommand extends Command
{
    private $extractionService;
    private $dateFormat = 'd-m-Y';

    protected static $defaultName = 'app:extract-days';

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
        $this->setDescription('Extracts statistics for a range of days.');

        $this->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'Csv filename to extract to.',
            null
        );

        $this->addArgument(
            'dateFrom',
            InputArgument::REQUIRED,
            sprintf('Extract searches from this day. Format: %s', $this->dateFormat),
            null
        );

        $this->addArgument(
            'dateTo',
            InputArgument::REQUIRED,
            sprintf('Extract searches until (including) this day. Format: %s', $this->dateFormat),
            null
        );

        $this->addOption(
            'types',
            null,
            InputOption::VALUE_OPTIONAL,
            'Comma separated list of types to extract (e.g. --types=hit,nohit,undetermined). Default is all.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @suppress PhanUndeclaredMethod
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $filename = $input->getArgument('filename');

        if (!$filename) {
            throw new \RuntimeException('Invalid filename argument.');
        }

        $rawDateFrom = $input->getArgument('dateFrom');
        $dateFrom = \DateTime::createFromFormat($this->dateFormat, $rawDateFrom);

        if (!$dateFrom) {
            throw new \RuntimeException('Invalid date format for dateFrom argument.');
        }

        $rawDateTo = $input->getArgument('dateTo');
        $dateTo = \DateTime::createFromFormat($this->dateFormat, $rawDateTo);

        if (!$dateTo) {
            throw new \RuntimeException('Invalid date format for dateTo argument.');
        }

        // Make sure dateTo is less than or equal to dateFrom.
        if ($dateTo > $dateTo) {
            throw new \RuntimeException('Invalid date selection: dateFrom should not be higher than dateTo.');
        }

        $numberOfDaysToSearch = (int) $dateTo->diff($dateFrom)->format('%a');

        $selectedDays = [$dateFrom];
        for ($i = 1; $i <= $numberOfDaysToSearch; ++$i) {
            $selectedDays[] = (new \DateTime($dateFrom->format('c')))->add(new \DateInterval('P'.$i.'D'));
        }

        $rawTypes = $input->getOption('types');

        $typesExploded = explode(',', $rawTypes);

        $types = array_reduce($typesExploded, function ($carry, $type) {
            if (in_array($type, ['hit', 'nohit', 'undetermined']) && !in_array($type, $carry)) {
                $carry[] = $type;
            }

            return $carry;
        }, []);

        // Setup target.
        $target = new CsvTarget($filename);

        if (count($types) > 0) {
            $target->setExtractionTypes($types);
        }

        // Setup progress bar.
        $progressBarSheet = new ProgressBar($output);
        $progressBarSheet->setFormat('[%bar%] %elapsed% (%memory%) - %message%');
        $this->extractionService->setProgressBar($progressBarSheet);

        // Extract.
        $this->extractionService->extractStatisticsForDays($selectedDays, $target);

        $style->success('Data extracted successfully to file: '.$filename);

        return 0;
    }
}
