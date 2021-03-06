<?php

/*
 * This file is part of the Congow\Orient package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Class Http
 *
 * @package     Orient
 * @subpackage  Foundation
 * @author      Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Congow\Orient\Foundation\Protocol\Adapter;

use Congow\Orient\Contract\Protocol\Adapter as ProtocolAdapter;
use Congow\Orient\Contract\Http\Client;
use Congow\Orient\Foundation\Binding;
use Congow\Orient\Exception\Query\SQL\Invalid as InvalidSQL;
use Congow\Orient\Exception\Http\Response\Void as VoidResponse;
use Congow\Orient\Http\Response;

class Http implements ProtocolAdapter
{
    
    protected $client;
    protected $result;
    
    /**
     * Instantiates a new adapter.
     *
     * @param Http\Client $client
     * @param String $host
     * @param String $port
     * @param String $username
     * @param String $password
     */
    public function __construct(Binding $binding)
    {
        $this->client = $binding;
    }
    
    /**
     * Executes some SQL against Congow\OrientDB via the HTTP binding.
     *
     * @param   string $sql
     * @return  mixed
     */
    public function execute($sql, $return = false, $fetchPlan = null)
    {
        if ($return) {
            $response = $this->getClient()->query($sql, null, -1, $fetchPlan);
        } else {
            $response = $this->getClient()->command($sql);
        }

        $this->checkResponse($response);
        
        if ($return) {
            $body = json_decode($response->getBody());

            $this->setResult($body->result);   
        }
        
        return true;
    }
    
    /**
     * Returns OrientDB's response to an HTTP request.
     * 
     * Pseudo code:
     * <code>
     * if ($adapter->execute('SELECT...')) {
     *   foreach ($adapter->getResult() as $records) {
     *     ...
     *   }
     * }
     * </code>
     * 
     * @see    Congow\Orient\Contract\Protocol\Adapter::getResult
     * @return Array|null
     */
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     * Checks whether the $response is valid.
     * A response is *not* considere valid when:
     * * it's void
     * * it returns a status code different from 2XX
     *
     * @param Response $response 
     */
    protected function checkResponse(Response $response = null)
    {
        if (!$response) {
            throw new VoidResponse(get_class($this->getClient()), $sql);
        }

        if (!in_array($response->getStatusCode(), $response->getValidStatusCodes())) {
            throw new InvalidSQL($response);   
        }
    }
    
    /**
     * Returns the internal client used to make requests to OrientDB.
     *
     * @return Congow\Orient\Contract\Http\Client
     */
    protected function getClient()
    {
        return $this->client;
    }
    
    /**
     * Sets the result of the execute() method.
     *
     * @param Array $result 
     */
    protected function setResult(Array $result)
    {
        $this->result = $result;
    }
}

