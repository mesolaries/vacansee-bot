<?php

namespace App\Command;

use App\Entity\Channel;
use App\Entity\Vacancy;
use App\Service\Api\Vacansee;
use App\Service\Bot\Bot;
use App\Service\Bot\ReplyMessages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class ChannelVacancySendCommand extends Command
{
    protected static $defaultName = 'app:channel:vacancy:send';

    private EntityManagerInterface $em;

    private Bot $bot;

    private Vacansee $api;

    public function __construct(EntityManagerInterface $em, Bot $bot, Vacansee $api, string $name = null)
    {
        $this->em = $em;
        $this->bot = $bot;
        $this->api = $api;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Send a vacancy to the channel(s)')
            ->addOption(
                'channel',
                'ch',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                "Specify channel(s) to send a vacancy. \nMapping: " .
                json_encode(Channel::CHANNELS, JSON_PRETTY_PRINT) . "\n",
                array_keys(Channel::CHANNELS)
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $channelIds = $input->getOption('channel');

        $channelRepository = $this->em->getRepository(Channel::class);

        $vacancyRepository = $this->em->getRepository(Vacancy::class);

        foreach ($channelIds as $channelId) {
            /** @var Channel|null $channel */
            $channel = $channelRepository->findOneBy(['channelId' => $channelId]);


            if (!$channel) {
                $io->warning(
                    "The channel id $channelId not found." .
                    "You either forgot to run app:channel:sync command or you've entered the wrong channel id option.\n" .
                    "Continuing with the next channel..."
                );

                continue;
            }

            $vacancy = $vacancyRepository->findOneNotSentVacancyByChannel($channel);

            if (!$vacancy) {
                $io->note("No new vacancies found for $channelId channel. Continuing with the next channel...");
                continue;
            }

            $vacancyResource = $this->api->getVacancy($vacancy->getVacancyId());

            $categoryName =
                str_replace(' ', '', ucwords($this->api->getResourceByUri($vacancyResource->category)->name));

            $salary = $vacancyResource->salary ?: 'Qeyd edilməyib';

            $text =
                sprintf(
                    ReplyMessages::VACANCY,
                    $vacancyResource->title,
                    $categoryName,
                    $vacancyResource->company,
                    $salary
                );

            $keyboard = new InlineKeyboardMarkup(
                [
                    [
                        ['text' => "Mənbə", 'url' => $vacancyResource->url],
                    ]
                ]
            );

            $this->bot->sendMessage($channelId, $text, 'HTML', true, null, $keyboard);

            $vacancy->setIsSent(true);

            $this->em->persist($vacancy);

            $this->em->flush();

            $io->note("Send a vacancy for $channelId channel.");
        }

        $io->success('All vacancy messages sent successfully!');

        return 0;
    }
}
