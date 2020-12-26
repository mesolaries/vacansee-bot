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
use Symfony\Component\DependencyInjection\ContainerInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class ChannelVacancySendCommand extends Command
{
    protected static $defaultName = 'app:channel:vacancy:send';

    private EntityManagerInterface $em;

    private Bot $bot;

    private Vacansee $api;

    private ContainerInterface $container;

    public function __construct(
        EntityManagerInterface $em,
        Bot $bot,
        Vacansee $api,
        ContainerInterface $container,
        string $name = null
    ) {
        $this->em        = $em;
        $this->bot       = $bot;
        $this->api       = $api;
        $this->container = $container;
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
            )
        ;
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

            if (Channel::CHANNELS[$channelId] == 'it') {
                $text .= "\n\n" . $this->shortenUrl($vacancyResource->url);

                $this->bot->sendMessage($channelId, $text, 'HTML', true, null);
            } else {
                $keyboard = new InlineKeyboardMarkup(
                    [
                        [
                            ['text' => "Mənbə", 'url' => $vacancyResource->url],
                        ]
                    ]
                );

                $this->bot->sendMessage($channelId, $text, 'HTML', true, null, $keyboard);
            }

            $vacancy->setIsSent(true);

            $this->em->persist($vacancy);

            $this->em->flush();

            $io->note("Send a vacancy for $channelId channel.");
        }

        $io->success('All vacancy messages sent successfully!');

        return 0;
    }

    /**
     * Shorten URL using YOURLS
     *
     * @param string $url
     *
     * @return string
     */
    private function shortenUrl(string $url): string
    {
        $signature = $this->container->getParameter('yourls_signature');
        $api_url   = 'https://a.vacansee.xyz/yourls-api.php';

        // Init the CURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
        curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            [     // Data to POST
                'url'       => $url,
                'format'    => 'json',
                'action'    => 'shorturl',
                'signature' => $signature,
            ]
        );

        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);

        // Do something with the result. Here, we echo the long URL
        $data = json_decode($data);

        return $data->shorturl;
    }
}
