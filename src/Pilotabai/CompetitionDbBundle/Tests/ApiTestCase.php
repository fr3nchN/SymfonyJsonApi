<?php

/*
 * Main Test Class for API Testing
 *
 * database name is setup in app/config/config_test
 * environment has been created in web/app_test.php
 * environment variable for test setup in phpunit.xml.dist
 *
 * create test database: $ bin/console doctrine:database:create --env=test
 * update test database structure: $ bin/console doctrine:schema:update --env=test --force
 * 
 */
namespace Pilotabai\CompetitionDbBundle\Tests;

use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Pilotabai\CompetitionDbBundle\Entity\Category;
use Pilotabai\CompetitionDbBundle\Entity\Game;
use Pilotabai\CompetitionDbBundle\Tests\ResponseAsserter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\PropertyAccess\PropertyAccess;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DomCrawler\Crawler;

class ApiTestCase extends KernelTestCase
{
    /**
     * @var Client
     */
    private static $staticClient;

    /**
     * @var
     */
    private static $history;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var
     */
    protected static $container;

    /**
     * @var
     */
    protected static $stack;

    /**
     * @var ResponseAsserter
     */
    private $responseAsserter;

    public static function setUpBeforeClass()
    {
        self::$container = [];
        self::$history = Middleware::history(self::$container);
        self::$stack = HandlerStack::create();
        self::$stack->push(self::$history);

        $baseUrl = (string) getenv('TEST_BASE_URL');
        self::$staticClient = new Client([
            'base_uri' => $baseUrl,
            'http_errors' => false,
            'handler' => self::$stack
        ]);

//        // guaranteeing that /app_test.php is prefixed to all URLs
//        self::$staticClient->getEmitter()
//            ->on('before', function(BeforeEvent $event) {
//                $path = $event->getRequest()->getPath();
//                if (strpos($path, '/api') === 0) {
//                    $event->getRequest()->setPath('/app_test.php'.$path);
//                }
//            });

        self::bootKernel();
    }

    protected function setUp()
    {
        $this->client = self::$staticClient;
        $this->purgeDatabase();
        $connection = $this->getEntityManager()->getConnection();
        $connection->exec("ALTER TABLE category AUTO_INCREMENT = 1;");
        $connection->exec("ALTER TABLE pb_user AUTO_INCREMENT = 1;");
        $connection->exec("ALTER TABLE game AUTO_INCREMENT = 1;");
        $connection->close();
        $this->createUser("nicolaspb");
    }
    /**
     * Clean up Kernel usage in this test.
     */
    protected function tearDown()
    {
        // purposefully not calling parent class, which shuts down the kernel
        $em = $this->getService('doctrine')->resetManager();
    }

    protected function onNotSuccessfulTest($e)
    {
        if (self::$history) {
            $this->printDebug('');
            $this->printDebug(sprintf('<comment>Transaction(s) made</comment>: <info>%s</info>',count(self::$container)));
            $this->printDebug('<error>Failure!</error> when making the following request:');
//            $this->printAllTransactions();
            $this->printLastRequestUrl();
            $this->debugLastResponse();
        }
        throw $e;
    }

    private function purgeDatabase()
    {
//        $purger = new ORMPurger($this->getService('doctrine')->getManager());
        $purger = new ORMPurger($this->getService('doctrine.orm.default_entity_manager'));
        $purger->purge();
    }

    protected function getService($id)
    {
        return self::$kernel->getContainer()
            ->get($id);
    }

    protected function printLastRequestUrl()
    {
        if (count(self::$container) == 0) {
            $this->printDebug('No request was made.');
        }
        $transaction = end(self::$container);
        $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', $transaction['request']->getMethod(), $transaction['request']->getUri()));
    }

    protected function debugLastResponse()
    {
        if (count(self::$container) == 0) {
            $this->printDebug('No request was made.');
        }
        $transaction = end(self::$container);
        $response = $transaction['response'];
        if(isset($response->getHeaders()['X-Debug-Token-Link'][0])) {
            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'X-Debug-Token-Link', $response->getHeaders()['X-Debug-Token-Link'][0]));
        }
//        $this->debugResponse($response);
    }

    protected function debugResponse(Response $response)
    {
//        $this->printDebug(AbstractMessage::getStartLineAndHeaders($response));
        $body = (string) $response->getBody();
        $contentType = $response->getHeader('Content-Type')[0];
        if ($contentType == 'application/json' || strpos($contentType, '+json') !== false) {
            $data = json_decode($body);
            if ($data === null) {
                // invalid JSON!
                $this->printDebug($body);
            } else {
                // valid JSON, print it pretty
                $this->printDebug(json_encode($data, JSON_PRETTY_PRINT));
            }
        } else {
            // the response is HTML - see if we should print all of it or some of it
            $isValidHtml = strpos($body, '</body>') !== false;
            if ($isValidHtml) {
                $this->printDebug('');
                $crawler = new Crawler($body);
                // very specific to Symfony's error page
                $isError = $crawler->filter('#traces-0')->count() > 0
                    || strpos($body, 'looks like something went wrong') !== false;
                if ($isError) {
                    $this->printDebug('There was an Error!!!!');
                    $this->printDebug('');
                } else {
                    $this->printDebug('HTML Summary (h1 and h2):');
                }
                // finds the h1 and h2 tags and prints them only
                foreach ($crawler->filter('h1, h2')->extract(array('_text')) as $header) {
                    // avoid these meaningless headers
                    if (strpos($header, 'Stack Trace') !== false) {
                        continue;
                    }
                    if (strpos($header, 'Logs') !== false) {
                        continue;
                    }
                    // remove line breaks so the message looks nice
                    $header = str_replace("\n", ' ', trim($header));
                    // trim any excess whitespace "foo   bar" => "foo bar"
                    $header = preg_replace('/(\s)+/', ' ', $header);
                    if ($isError) {
                        $this->printErrorBlock($header);
                    } else {
                        $this->printDebug($header);
                    }
                }
                $profilerUrl = $response->getHeader('X-Debug-Token-Link');
                if ($profilerUrl) {
                    $fullProfilerUrl = $response->getHeader('Host').$profilerUrl;
                    $this->printDebug('');
                    $this->printDebug(sprintf(
                        'Profiler URL: <comment>%s</comment>',
                        $fullProfilerUrl
                    ));
                }
                // an extra line for spacing
                $this->printDebug('');
            } else {
                $this->printDebug($body);
            }
        }
    }

    /**
     * Print a message out - useful for debugging
     *
     * @param $string
     */
    protected function printDebug($string)
    {
        if ($this->output === null) {
            $this->output = new ConsoleOutput();
        }
        $this->output->writeln($string);
    }

    /**
     * Print a debugging message out in a big red block
     *
     * @param $string
     */
    protected function printErrorBlock($string)
    {
        if ($this->formatterHelper === null) {
            $this->formatterHelper = new FormatterHelper();
        }
        $output = $this->formatterHelper->formatBlock($string, 'bg=red;fg=white', true);
        $this->printDebug($output);
    }

    protected function printAllTransactions() {
        $this->printDebug('--- All Transactions ---');
        foreach (self::$container as $transaction) {
            $this->printDebug('### Request');
            $request = $transaction['request'];
            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Uri', $request->getUri()));
//            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Scheme', $request->getUri()->getScheme()));
//            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Host', $request->getUri()->getHost()));
//            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Port', $request->getUri()->getPort()));
            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Path', $request->getUri()->getPath()));
//            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Query', $request->getUri()->getQuery()));
            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Method', $request->getMethod()));
            $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Body', $request->getBody()));
            foreach ($request->getHeaders() as $name => $values) {
//                $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', $name, implode(', ', $values)));
            }
            if ($transaction['response']) {
                $this->printDebug('### Response');
                $response = $transaction['response'];
                foreach ($response->getHeaders() as $name => $values) {
                    $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', $name, implode(', ', $values)));
                }
//                $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'Body', $response->getBody()));
                $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'StatusCode', $response->getStatusCode()));
                $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'ReasonPhrase', $response->getReasonPhrase()));
//                $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', 'ProtocolVersion', $response->getProtocolVersion()));

//                $this->printDebug(sprintf('<comment>%s</comment>: <info>%s</info>', $transaction['response']->getStatusCode(), $transaction['response']->getHeaders()['X-Debug-Token-Link'][0]));
            } elseif ($transaction['error']) {
                $this->printDebug($transaction['error']); // Does not work
            }
            //var_dump($transaction['options']); //> dumps the request options of the sent request.
        }
    }

    /**
     * @param array $data
     * @return Category
     * @throws \Throwable
     * @throws \TypeError
     */
    protected function createCategory(array $data)
    {
//        $this->printDebug('createCategory');
        $accessor = PropertyAccess::createPropertyAccessor();
        $category = new Category();
        $data = array_merge(array(
            "competition" => "Test: A competition test name",
            "speciality" => "Test: A speciality test name",
            "level" => "Test: A level test name"
        ), $data);
        foreach ($data as $key => $value) {
            $accessor->setValue($category, $key, $value);
        }
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
        return $category;
    }

    protected function createUser($username, $plainPassword = '123456pw')
    {
        //$user = $this->getUserManager()->createUser();
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@foo.com');
        $password = $this->getService('security.password_encoder')
            ->encodePassword($user, $plainPassword);
        $user->setPassword($password);
        $user->setEnabled(true);
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
        return $user;
    }

    protected function createGame(array $data, Category $category)
    {
//        $this->printDebug('createGame');
        $accessor = PropertyAccess::createPropertyAccessor();
        $game = new Game();
        $data = array_merge(array(
            "phase" => "Test: A phase name",
        ), $data);
        foreach ($data as $key => $value) {
            $accessor->setValue($game, $key, $value);
        }
        $game->setCategory($category);
        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
        return $game;
    }

    protected function createGames($games)
    {
        foreach ($games as $game) {
            $this->getEntityManager()->persist($game);
        }
        $this->getEntityManager()->flush();
        return;
    }

    protected function getAuthorizedHeaders($username, $headers = array())
    {
        $token = $this->getService('lexik_jwt_authentication.encoder')
            ->encode(['username' => $username]);
        $headers['Authorization'] = 'Bearer '.$token;
        return $headers;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine.orm.entity_manager');
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->getService('fos_user.user_manager');
    }

    /**
     * @return ResponseAsserter
     */
    protected function asserter()
    {
        if ($this->responseAsserter === null) {
            $this->responseAsserter = new ResponseAsserter();
        }
        return $this->responseAsserter;
    }
}