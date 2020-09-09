<?php

namespace App\Command;

use App\Entity\Channel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ChannelSyncCommand extends Command
{
    protected static $defaultName = 'app:channel:sync';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Syncs channels IDs and channel categories with Channel entity');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $channels = Channel::CHANNELS;

        $channelRepository = $this->em->getRepository(Channel::class);

        foreach ($channels as $id => $slug) {
            $channel = $channelRepository->findOneBy(['channelId' => $id]) ?? new Channel();

            $channel->setChannelId($id);
            $channel->setCategorySlug($slug);

            $this->em->persist($channel);
        }

        $this->em->flush();

        $io->success('Channels successfully synced with DB.');

        return 0;
    }
}
