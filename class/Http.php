<?php

Class Http
{
    const CURL_TIMEOUT           = 10;
    const MULTIPLE_REQUEST_LIMIT = 20;
    const MAX_REQUESTS           = 10;
    
    /**
     * Multiple requests: at most 10 request urls for each request
     * @param $requests
     * @return array
     */
    public function multipleRequests($requests)
    {
        $requestsArray = [];
        $responseArray = [];
        foreach ($requests as $i => $request) {
            if (0 === $i % self::MAX_REQUESTS) {
                if (!empty($requestsArray)) {
                    $res           = $this->multipleThreadsRequest($requestsArray);
                    $responseArray = $responseArray + $res;
                }
                $requestsArray = [];
            }
            $requestsArray[$i] = $request;
        }
        // last requests
        if (!empty($requestsArray)) {
            $res           = $this->multipleThreadsRequest($requestsArray);
            $responseArray = $responseArray + $res;
        }
        
        return $responseArray;
    }
    
    /**
     * Multiple threads curl requests
     * @param $urls
     * @return array
     */
    private function multipleThreadsRequest($urls)
    {
        $mh        = curl_multi_init();
        $curlArray = [];
        foreach ($urls as $i => $url) {
            $curlArray[$i] = curl_init($url);
            curl_setopt($curlArray[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlArray[$i], CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
            curl_setopt($curlArray[$i], CURLOPT_CONNECTTIMEOUT, self::CURL_TIMEOUT);
            curl_multi_add_handle($mh, $curlArray[$i]);
        }
        $running = null;
        do {
            if ($running > 0) {
                usleep(10000);
            }
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        $res = [];
        foreach ($urls as $i => $url) {
            $res[$i] = curl_multi_getcontent($curlArray[$i]);
            curl_multi_remove_handle($mh, $curlArray[$i]);
        }
        
        unset($curlArray);
        curl_multi_close($mh);
        
        return $res;
    }
}
