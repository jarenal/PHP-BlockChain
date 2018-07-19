<?php

namespace Jarenal;

use DI\Annotation\Inject;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Server as ReactServer;
use React\Http\Response;
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use Sikei\React\Http\Middleware\CorsMiddleware;

class Daemon
{
    /**
     * @Inject("DI\Container")
     * @var \DI\Container $container
     */
    private $container;

    private $loop;

    private $settings = [];

    private $serverApi;

    private $serverWeb;

    public function __construct()
    {
        $this->loop = Factory::create();

        $this->settings = [
            'allow_credentials' => true,
            'allow_origin' => ['*'],
            'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
            'allow_headers' => [
                'DNT',
                'X-Custom-Header',
                'Keep-Alive',
                'User-Agent',
                'X-Requested-With',
                'If-Modified-Since',
                'Cache-Control',
                'Content-Type',
                'Content-Range',
                'Range'
            ],
            'expose_headers' => [
                'DNT',
                'X-Custom-Header',
                'Keep-Alive',
                'User-Agent',
                'X-Requested-With',
                'If-Modified-Since',
                'Cache-Control',
                'Content-Type',
                'Content-Range',
                'Range'
            ],
            'max_age' => 60 * 60 * 24 * 20, // preflight request is valid for 20 days
        ];
    }

    public function start()
    {
        $this->startApiServer();
        $this->startWebServer();
        $this->loop->run();
    }

    public function addPeriodicTimer($time, $callback)
    {
        $this->loop->addPeriodicTimer($time, $callback);
    }

    private function startApiServer()
    {
        echo "Starting API Server on port " . PORT_API . "\n";

        $this->serverApi = new ReactServer([
            new CorsMiddleware($this->settings),
            function (ServerRequestInterface $request) {

                $body = $request->getBody();
                $data = json_decode($body->getContents(), true);
                $apiController = $this->container->get(Controller\ApiController::class);
                return $apiController->execute($data, $request->getServerParams());
            }
        ]);

        $this->serverApi->on('error', function (\Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            if ($e->getPrevious() !== null) {
                $previousException = $e->getPrevious();
                echo $previousException->getMessage() . PHP_EOL;
            }
        });

        $socketApi = new SocketServer(BIND_ADDRESS . ":" . PORT_API, $this->loop);
        $this->serverApi->listen($socketApi);
    }

    private function startWebServer()
    {
        echo "Starting Web Server on port " . PORT_WEB . "\n";

        $this->serverWeb = new ReactServer(function (ServerRequestInterface $request) {

            $requestTarget = $request->getRequestTarget();
            $filePath = __DIR__ . "/../public" . $requestTarget;

            if (file_exists($filePath) && $requestTarget !== "/" && $requestTarget !== "/explorer") {
                $mimeType = my_mime_content_type($filePath);
                return new Response(
                    200,
                    array(
                        'Content-Type' => $mimeType
                    ),
                    file_get_contents($filePath)
                );
            } else {
                $webController = $this->container->get(Controller\WebController::class);

                if ($requestTarget === "/explorer") {
                    return $webController->explorer();
                } else {
                    return $webController->index();
                }
            }
        });

        $socketWeb = new SocketServer(BIND_ADDRESS . ":" . PORT_WEB, $this->loop);
        $this->serverWeb->listen($socketWeb);
    }
}
