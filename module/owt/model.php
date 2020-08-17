<?php
/**
 * The model file of owt module of XXB.
 *
 * @copyright   Copyright 2009-2020 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZOSL (https://zpl.pub/page/zoslv1.html)
 * @author      Wenrui LI <liwenrui@easycorp.ltd>
 * @package     owt
 * @version     $Id$
 * @link        https://xuanim.com
 */
?>
<?php
class owtModel extends model
{
    /**
     * Make a header with signature for the use of owt api requests.
     *
     * @return array
     */
    function makeHeader()
    {
        /* $cnonce should be an int between 0 and 99999. */
        $cnonce = rand(0, 99999);

        /* $timestamp should include milliseconds. */
        $timestamp = round(microtime(true) * 1000);

        $auth = array();
        $auth['Mauth realm']            = 'http://webrtc.intel.com';
        $auth['mauth_signature_method'] = 'HMAC_SHA256';
        $auth['mauth_serviceid']        = $this->config->owt->serviceId;
        $auth['mauth_cnonce']           = $cnonce;
        $auth['mauth_timestamp']        = $timestamp;

        /* Generate the signature and convert to base64. */
        $rawSignature = hash_hmac('sha256', $timestamp . ',' . $cnonce, $this->config->owt->serviceKey);
        $auth['mauth_signature'] = base64_encode($rawSignature);

        /* Implode the keys and values of the $auth array into a string connected by comma and linebreak along with contentType json. */
        return array('Authorization: ' . urldecode(http_build_query($auth, null, ",")), 'Content-Type: application/json');
    }

    /**
     * Make request to owt server with given path, method and params.
     *
     * @param  string       $path
     * @param  string       $method
     * @param  string|array $params
     * @return string
     */
    function makeRequest($path, $method = 'GET', $params = '')
    {
        /* Convert params to JSON string if it is object or array. */
        if(is_array($params)) $params = json_encode($params);

        /* Prepare curl handler and set its params. */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$this->config->owt->host}:{$this->config->owt->managePort}{$path}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->makeHeader());
        /* Allow the specification of other methods like DELETE and PUT. */
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        /* Allow "unsafe" connections. */
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        /* Execute request and return result. */
        $result = curl_exec($ch);
        return $result;
    }

    /**
     * Create a room with given name in owt server.
     *
     * @param  string $name
     * @access public
     * @return array
     */
    public function createRoom($name)
    {
        $roomConfig = array('name' => $name);
        return $this->makeRequest('/v1/rooms', 'POST', $roomConfig);
    }

    /**
     * Get a room by given id from owt server.
     *
     * @param  string $roomId
     * @access public
     * @return array
     */
    public function getRoom($roomId)
    {
        return $this->makeRequest("/v1/rooms/$roomId");
    }

    /**
     * Create room list from owt server.
     *
     * @access public
     * @return array
     */
    public function listRooms()
    {
        return $this->makeRequest('/v1/rooms');
    }

    /**
     * Delete a room by given id in owt server.
     *
     * @param  string $roomId
     * @access public
     * @return array
     */
    public function deleteRoom($roomId)
    {
        return $this->makeRequest("/v1/rooms/$roomId", 'DELETE');
    }

    /**
     * Update a room with given id and room data in owt server.
     *
     * @param  string       $roomId
     * @param  string|array $roomData
     * @access public
     * @return array
     */
    public function updateRoom($roomId, $roomData)
    {
        return $this->makeRequest("/v1/rooms/$roomId", 'PUT', $roomData);
    }

    /**
     * Get current owt configuration.
     *
     * @access public
     * @return object
     */
    public function getConfiguration()
    {
        $owt = $this->config->owt;
        if(empty($owt->host) || empty($owt->serviceId) || empty($owt->serviceKey)) return false;

        $conf = new stdClass();
        $conf->host = $owt->host;
        $conf->api  = "https://{$owt->host}:{$owt->apiPort}";

        return $conf;
    }
}
