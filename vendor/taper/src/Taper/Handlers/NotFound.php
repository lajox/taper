<?php

namespace Taper\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Taper\Http\Body;

/**
 * Default Slim application not found handler.
 *
 * It outputs a simple message in either JSON, XML or HTML based on the
 * Accept header.
 */
class NotFound extends AbstractHandler
{
    /**
     * Invoke not found handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $contentType = $this->determineContentType($request);
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonNotFoundOutput();
                break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXmlNotFoundOutput();
                break;

            case 'text/html':
                $output = $this->renderHtmlNotFoundOutput($request);
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response->withStatus(404)
                        ->withHeader('Content-Type', $contentType)
                        ->withBody($body);
    }

    /**
     * Return a response for application/json content not found
     *
     * @return ResponseInterface
     */
    protected function renderJsonNotFoundOutput()
    {
        return '{"message":"Not found"}';
    }

    /**
     * Return a response for xml content not found
     *
     * @return ResponseInterface
     */
    protected function renderXmlNotFoundOutput()
    {
        return '<root><message>Not found</message></root>';
    }

    /**
     * Return a response for text/html content not found
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     *
     * @return ResponseInterface
     */
    protected function renderHtmlNotFoundOutput(ServerRequestInterface $request)
    {
        $homeUrl = (string)($request->getUri()->withPath('')->withQuery('')->withFragment(''));
        return include(dirname(__DIR__).'/Tpl/debug.html');
    }
}
