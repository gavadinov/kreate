<?php

namespace Kreate\Http;

class Response
{
    private $headers = array(),
            $content,
            $contentType = 'text/html',
            $charset = 'utf-8',
            $status = array(
                'message' => 'OK',
                'code' => 200,
            );

    /*
     * Singleton instance of the request
     */
    private static $instance;

    /**
     * Singleton
     *
     * @return Response
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {}

    public function prepareRedirect($url, $code = 301)
    {
        $this->setStatus($code, 'Redirect');
        $this->setHeader('Location: ' . $url);
        return $this;
    }

    /**
     * Set the http status
     *
     * @param int $code
     * @param string $message
     */
    public function setStatus($code, $message)
    {
        $this->status = array(
            'message' => $message,
            'code' => $code
        );
    }

    /**
     * Set http header
     *
     * @param string $header
     */
    public function setHeader($header)
    {
        $this->headers[] = $header;
    }

    /**
     * Set the content type
     *
     * @param string $type
     */
    public function setContentType($type)
    {
        $this->contentType = $type;
    }

    /**
     * Set the content
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Sends the response back to the client
     */
    public function send()
    {
        header("HTTP/1.1 {$this->status['code']}: {$this->status['message']}", true, $this->status['code']);
        foreach ($this->headers as $header) {
            header($header);
        }

        header('Content-type: ' . $this->contentType . '; charset=' . $this->charset, true);

        echo $this->content;

        die;
    }
}
