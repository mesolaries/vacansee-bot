<?php

namespace App\Command;

use App\Entity\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ChannelVacancyClearCommand extends Command
{
    protected static $defaultName = 'app:channel:vacancy:clear';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Clear old vacancies from the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $vacancyRepository = $this->em->getRepository(Vacancy::class);

        $vacancies = $vacancyRepository->findSentVacanciesByGotDateTillThisMonth();

        $count = count($vacancies);

        foreach ($vacancies as $vacancy) {
            $this->em->remove($vacancy);
        }

        $this->em->flush();

        $io->note("Cleared $count vacancies");
        $io->success('The database cleared successfully!');

        return 0;
    }
}
