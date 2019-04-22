<?php
namespace App\Command;

use App\Client\GdzieJestMevoClient;
use App\Client\MevoClient;
use App\Entity\RawStation;
use App\Entity\Station;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;

class FetchStationsCommand extends Command
{
    protected static $defaultName = 'mevo:fetch:stations';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MevoClient
     */
    protected $mevoClient;

    /**
     * @var GdzieJestMevoClient
     */
    protected $gjmClient;

    /**
     * FetchCommand constructor.
     * @param EntityManager $em
     * @param MevoClient $mevoClient
     * @param GdzieJestMevoClient $gjmClient
     */
    public function __construct(EntityManager $em, MevoClient $mevoClient, GdzieJestMevoClient $gjmClient)
    {
        $this->em = $em;
        $this->mevoClient = $mevoClient;
        $this->gjmClient = $gjmClient;

        parent::__construct();
    }

    /**
     * @param RawStation[] $rawStations
     * @param array $stationNames
     * @throws ORMException
     */
    private function updateStations($rawStations, $stationNames)
    {
        $stationRepo = $this->em->getRepository(Station::class);

        foreach($rawStations as $code => $rawStation) {
            $station = $stationRepo->findOneBy(["code" => (string) $code]);

            if (empty($station)) {
                $station = new Station();
            }
            
            $station->setCode($rawStation->getCode());
            $station->setCity($rawStation->getCity());
            $station->setRacks($rawStation->getRacks());
            $station->setBikes($rawStation->getBikes());
            $station->setBookedBikes($rawStation->getBookedBikes());
            $station->setLocation($rawStation->getLocation());

            if (!empty($stationNames[$rawStation->getUid()])) {
                $station->setName($stationNames[$rawStation->getUid()]);
            }

            $this->em->persist($station);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stations = $this->mevoClient->fetchStations();
        $stationNames = $this->gjmClient->fetchStationNames();
        $this->updateStations($stations, $stationNames);

        $this->em->flush();

        echo count($stations)."\n";
    }
}