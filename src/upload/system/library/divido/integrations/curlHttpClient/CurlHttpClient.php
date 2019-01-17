<?php

namespace Divido\Integrations\Lib\CurlHttpClient;

use Psr\Http\Message\UriInterface;

/**
 * Class Curl HTTP client
 *
 * @author Andrew Smith <andrew.smith@divido.com>
 * @copyright (c) 2019, Divido
 * @package Divido\IntegrationsLib
 */
class CurlHttpClient implements \Divido\MerchantSDK\HttpClient\IHttpClient
{

    private $ch;

    ########################################################
    #                                                      #
    #             P U B L I C    M E T H O D S             #
    #                                                      #
    ########################################################

    public function __construct(){
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    }
    
    /**
     * Submit an HTTP GET request
     *
     * @param UriInterface $url The url to send the request to $uri
     * @param array $headers A key/value pair array of headers to send with the request
     *
     * @return ResponseInterface The HTTP response (PSR implementation)
     *
     */
    public function get(UriInterface $url, array $headers = [])
    {
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);

        curl_setopt($this->ch, CURLOPT_URL, $url->__toString());

        $header = Array();
        foreach($headers as $key=>$value){
            $header[] = "{$key}: {$value}";
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);

        $payload = curl_exec($this->ch);
        
        $response = $this->make_psr_response($payload);

        return $response;
    }

    /**
     * Submit an HTTP POST request
     *
     * @param UriInterface $url The url to send the request to $uri
     * @param array $headers A key/value pair array of headers to send with the request
     * @param string $payload The payload to send with the request
     *
     * @return ResponseInterface The HTTP response (PSR implementation)
     *
     */
    public function post(UriInterface $url, array $headers = [], $payload = '')
    {
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $payload);

        curl_setopt($this->ch, CURLOPT_URL, $url->__toString());

        $header = array();
        foreach ($headers as $key => $value) {
            $header[] = "{$key}: {$value}";
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);

        $responseStr = curl_exec($this->ch);

        $psrResponse = $this->make_psr_response($responseStr);

        return $psrResponse;
    }

    /**
     * Submit an HTTP DELETE request
     *
     * @param UriInterface $url The url to send the request to $uri
     * @param array $headers A key/value pair array of headers to send with the request
     *
     * @return ResponseInterface The HTTP response (PSR implementation)
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(UriInterface $url, array $headers = [])
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($this->ch, CURLOPT_URL, $url->__toString());

        $header = array();
        foreach ($headers as $key => $value) {
            $header[] = "{$key}: {$value}";
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);

        $responseStr = curl_exec($this->ch);

        $psrResponse = $this->make_psr_response($responseStr);

        return $psrResponse;
    }

    /**
     * Submit an HTTP PATCH request
     *
     * @param UriInterface $url The url to send the request to $uri
     * @param array $headers A key/value pair array of headers to send with the request
     * @param string $payload The payload to send with the request
     *
     * @return ResponseInterface The HTTP response (PSR implementation)
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function patch(UriInterface $url, array $headers = [], $payload = '')
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($this->ch, CURLOPT_URL, $url->__toString());

        $header = array();
        foreach ($headers as $key => $value) {
            $header[] = "{$key}: {$value}";
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);

        $responseStr = curl_exec($this->ch);

        $psrResponse = $this->make_psr_response($responseStr);

        return $psrResponse;
    }

    ########################################################
    #                                                      #
    #          P R O T E C T E D    M E T H O D S          #
    #                                                      #
    ########################################################

    // No protected methods

    ########################################################
    #                                                      #
    #            P R I V A T E    M E T H O D S            #
    #                                                      #
    ########################################################

    /**
     * create a psr response from the cURL payload
     *
     * @param string $payload
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function make_psr_response($payload){
        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $header = substr($payload, 0, $header_size);

        $header_info = explode("\n", $header);
        list($version, $status_code, $reason) = explode(" ", $header_info[0], 3);
        
        unset($header_info[0]);
        
        require_once('PsrCurlResponse.php');
        $response = new PsrCurlResponse;
        $response
            ->withStatus($status_code, $reason)
            ->withProtocolVersion($version);

        foreach($header_info as $header){
            @list($name, $value) = explode(": ", $header,2);
            if(!empty($value))$response->withHeader($name, $value);
        }

        $bodyStr = substr($payload, $header_size);
        require_once('PsrStream.php');
        $stream = fopen('php://temp','w+');
        $body = new PsrStream($stream);
        $write = $body->write($bodyStr);
        $body->seek(0);
        $response->withBody($body);

        return $response;
    }
}
