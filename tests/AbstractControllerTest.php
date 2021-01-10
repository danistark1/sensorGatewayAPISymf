<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\Tests;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AbstractControllerTest extends WebTestCase
{
    /** @var EntityManager $manager */
    private $manager;

    /** @var ORMExecutor $executor */
    private $executor;

    /** @var KernelBrowser $client */
    public static  $client = null;

    public function setUp(): void {
        // Kernel Client can only be called once in a test.
        if (self::$client === null) {
            self::$client = static::createClient();
        }

        // Configure variables
        $this->manager = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->executor = new ORMExecutor($this->manager, new ORMPurger());

        // Run the schema update tool using our entity metadata
        $schemaTool = new SchemaTool($this->manager);
        $schemaTool->updateSchema($this->manager->getMetadataFactory()->getAllMetadata());
    }

    protected function loadFixture($fixture)
    {
        $loader = new Loader();
        $fixtures = is_array($fixture) ? $fixture : [$fixture];
        foreach ($fixtures as $item) {
            $loader->addFixture($item);
        }
        $this->executor->execute($loader->getFixtures());
    }

    public function tearDown(): void {
        (new SchemaTool($this->manager))->dropDatabase();
    }
}
