<?php

namespace App\Command;

use App\Entity\Channel;
use App\Entity\Vacancy;
use App\Service\Api\Vacansee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ChannelVacancyGetCommand extends Command
{
    protected static $defaultName = 'app:channel:vacancy:get';

    private EntityManagerInterface $em;

    private Vacansee $api;

    public function __construct(EntityManagerInterface $em, Vacansee $api, string $name = null)
    {
        $this->em = $em;
        $this->api = $api;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Get vacancies from API and add to the queue')
            ->addOption(
                'channel',
                'ch',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                "Specify channel(s) the vacancies to get for. \nMapping: ".
                json_encode(Channel::CHANNELS, JSON_PRETTY_PRINT)."\n",
                array_keys(Channel::CHANNELS)
            );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $channelIds = $input->getOption('channel');

        $channelRepository = $this->em->getRepository(Channel::class);
        $vacancyRepository = $this->em->getRepository(Vacancy::class);

        $today = new \DateTime('today midnight', new \DateTimeZone('Asia/Baku'));
        $today->setTimezone(new \DateTimeZone('UTC'));

        $newVacancies = $this->api->getVacancies(
            [
                'createdAt[after]' => $today->format('Y-m-d H:i'),
            ]
        );

        foreach ($channelIds as $channelId) {
            $io->note("Working with $channelId channel");

            /** @var Channel|null $channel */
            $channel = $channelRepository->findOneBy(['channelId' => $channelId]);

            if (!$channel) {
                $io->warning(
                    "The channel id $channelId not found.".
                    "You either forgot to run app:channel:sync command or you've entered the wrong channel id option.\n".
                    'Continuing with the next channel...'
                );

                continue;
            }

            $newVacanciesCount = 0;

            foreach ($newVacancies as $newVacancy) {
                $channelCategorySlug = Channel::CHANNELS[$channelId];

                if (null !== $channelCategorySlug && $channelCategorySlug !== $newVacancy->category->slug) {
                    continue;
                }

                if ($vacancyRepository->findOneBy(['vacancyId' => $newVacancy->id, 'channel' => $channel])) {
                    continue;
                }

                ++$newVacanciesCount;

                $vacancy = new Vacancy();
                $vacancy->setChannel($channel);
                $vacancy->setVacancyId($newVacancy->id);

                $this->em->persist($vacancy);
            }

            $this->em->flush();

            $io->note("Found $newVacanciesCount new vacancies and added for the $channelId channel");
        }

        $io->success('All channel vacancies are updated successfully');

        return 0;
    }
}
