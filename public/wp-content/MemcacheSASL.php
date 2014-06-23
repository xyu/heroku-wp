<?php

class MemcacheSASL
{
    protected $_request_format = 'CCnCCnNNNN';
    protected $_response_format = 'Cmagic/Copcode/nkeylength/Cextralength/Cdatatype/nstatus/Nbodylength/NOpaque/NCAS1/NCAS2';

    const OPT_COMPRESSION = -1001;

    const MEMC_VAL_TYPE_MASK = 0xf;
    const MEMC_VAL_IS_STRING = 0;
    const MEMC_VAL_IS_LONG = 1;
    const MEMC_VAL_IS_DOUBLE = 2;
    const MEMC_VAL_IS_BOOL = 3;
    const MEMC_VAL_IS_SERIALIZED = 4;

    const MEMC_VAL_COMPRESSED = 16; // 2^4

    protected function _build_request($data)
    {
        $valuelength = $extralength = $keylength = 0;
        if (array_key_exists('extra', $data)) {
            $extralength = strlen($data['extra']);
        }
        if (array_key_exists('key', $data)) {
            $keylength = strlen($data['key']);
        }
        if (array_key_exists('value', $data)) {
            $valuelength = strlen($data['value']);
        }
        $bodylength = $extralength + $keylength + $valuelength;
        $ret = pack($this->_request_format, 
                0x80, 
                $data['opcode'], 
                $keylength,
                $extralength,
                array_key_exists('datatype', $data) ? $data['datatype'] : null,
                array_key_exists('status', $data) ? $data['status'] : null,
                $bodylength, 
                array_key_exists('Opaque', $data) ? $data['Opaque'] : null,
                array_key_exists('CAS1', $data) ? $data['CAS1'] : null,
                array_key_exists('CAS2', $data) ? $data['CAS2'] : null
            );

        if (array_key_exists('extra', $data)) {
            $ret .= $data['extra'];
        }

        if (array_key_exists('key', $data)) {
            $ret .= $data['key'];
        }

        if (array_key_exists('value', $data)) {
            $ret .= $data['value'];
        }
        return $ret;
    }

    protected function _show_request($data)
    {
        if (empty($data)) {
            return array();
        }
        $array = unpack($this->_response_format, $data);
        return $array;
    }

    protected function _send($data)
    {
        $send_data = $this->_build_request($data);
        fwrite($this->_fp, $send_data);
        return $send_data;
    }

    protected function _recv()
    {
        $data = fread($this->_fp, 24);
        $array = $this->_show_request($data);
	if (!empty($array['bodylength'])) {
	    $bodylength = $array['bodylength'];
	    $data = '';
	    while ($bodylength > 0) {
		$recv_data = fread($this->_fp, $bodylength);
		$bodylength -= strlen($recv_data);
		$data .= $recv_data;
	    }

	    if ($array['extralength']) {
		$extra_unpacked = unpack('Nint', substr($data, 0, $array['extralength']));
		$array['extra'] = $extra_unpacked['int'];
	    }
	    $array['key'] = substr($data, $array['extralength'], $array['keylength']);
	    $array['body'] = substr($data, $array['extralength'] + $array['keylength']);
	}
        return $array;
    }

    public function __construct()
    {
    }

    public function __destruct()
    {
        $this->quit();
    }

    public function listMechanisms()
    {
        $this->_send(array('opcode' => 0x20));
        $data = $this->_recv();
        return explode(" ", $data['body']);
    }

    public function setSaslAuthData($user, $password)
    {
        $this->_send(array(
                    'opcode' => 0x21,
                    'key' => 'PLAIN',
                    'value' => '' . chr(0) . $user . chr(0) . $password
                    ));
        $data = $this->_recv();

        if ($data['status']) {
            throw new Exception($data['body'], $data['status']);
        }
    }

    public function addServer($host, $port, $weight = 0)
    {
        $this->_fp = stream_socket_client($host . ':' . $port);
    }

    public function get($key)
    {   
        $sent = $this->_send(array(
                    'opcode' => 0x00,
                    'key' => $key,
                    ));
	$data = $this->_recv();
	if (0 == $data['status']) {
            if ($data['extra'] & self::MEMC_VAL_COMPRESSED) {
                $body = gzuncompress($data['body']);
            } else {
                $body = $data['body'];
            }

            $type = $data['extra'] & self::MEMC_VAL_TYPE_MASK;

            switch ($type) {
            case self::MEMC_VAL_IS_STRING:
                $body = strval($body);
                break;

            case self::MEMC_VAL_IS_LONG:
                $body = intval($body);
                break;

            case self::MEMC_VAL_IS_DOUBLE:
                $body = doubleval($body);
                break;

            case self::MEMC_VAL_IS_BOOL:
                $body = $body ? true : false;
                break;

            case self::MEMC_VAL_IS_SERIALIZED:
                $body = unserialize($body);
                break;
            }

            return $body;
        }
        return FALSE;
    }

    /**
     * process value and get flag
     * 
     * @param int $flag
     * @param mixed $value 
     * @access protected
     * @return array($flag, $processed_value)
     */
    protected function _processValue($flag, $value)
    {
        if (is_string($value)) {
            $flag |= self::MEMC_VAL_IS_STRING;
        } elseif (is_long($value)) {
            $flag |= self::MEMC_VAL_IS_LONG;
        } elseif (is_double($value)) {
            $flag |= self::MEMC_VAL_IS_DOUBLE;
        } elseif (is_bool($value)) {
            $flag |= self::MEMC_VAL_IS_BOOL;
        } else {
            $value = serialize($value);
            $flag |= self::MEMC_VAL_IS_SERIALIZED;
        }

        if (array_key_exists(self::OPT_COMPRESSION, $this->_options) and $this->_options[self::OPT_COMPRESSION]) {
            $flag |= self::MEMC_VAL_COMPRESSED;
	    $value = gzcompress($value);
        }
        return array($flag, $value);
    }

    public function add($key, $value, $expiration = 0)
    {
        list($flag, $value) = $this->_processValue(0, $value);

        $extra = pack('NN', $flag, $expiration);
        $sent = $this->_send(array(
                    'opcode' => 0x02,
                    'key' => $key,
                    'value' => $value,
                    'extra' => $extra,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function set($key, $value, $expiration = 0)
    {
        list($flag, $value) = $this->_processValue(0, $value);

        $extra = pack('NN', $flag, $expiration);
        $sent = $this->_send(array(
                    'opcode' => 0x01,
                    'key' => $key,
                    'value' => $value,
                    'extra' => $extra,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function delete($key)
    {
        $sent = $this->_send(array(
                    'opcode' => 0x04,
                    'key' => $key,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function flush()
    {
        $sent = $this->_send(array(
                    'opcode' => 0x08
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function quit()
    {
	if ( NULL !== $this->_fp ) {
		$sent = $this->_send(array(
			    'opcode' => 0x07
			    ));
		$data = $this->_recv();

		fclose($this->_fp);
		$this->_fp = NULL;
	}

        return TRUE;
    }

    public function replace($key, $value, $expiration = 0)
    {
        list($flag, $value) = $this->_processValue(0, $value);

        $extra = pack('NN', $flag, $expiration);
        $sent = $this->_send(array(
                    'opcode' => 0x03,
                    'key' => $key,
                    'value' => $value,
                    'extra' => $extra,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    protected function _upper($num)
    {
        return $num << 32;
    }

    protected function _lower($num)
    {
        return $num % (2 << 32);
    }

    public function increment($key, $offset = 1)
    {
        $initial_value = 0;
        $extra = pack('N2N2N', $this->_upper($offset), $this->_lower($offset), $this->_upper($initial_value), $this->_lower($initial_value), 0);
        $sent = $this->_send(array(
                    'opcode' => 0x05,
                    'key' => $key,
                    'extra' => $extra,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function decrement($key, $offset = 1)
    {
        $initial_value = 0;
        $extra = pack('N2N2N', $this->_upper($offset), $this->_lower($offset), $this->_upper($initial_value), $this->_lower($initial_value), 0);
        $sent = $this->_send(array(
                    'opcode' => 0x06,
                    'key' => $key,
                    'extra' => $extra,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Get statistics of the server
     *
     * @param string $type The type of statistics to fetch. Valid values are 
     *                     {reset, malloc, maps, cachedump, slabs, items,
     *                     sizes}. According to the memcached protocol spec
     *                     these additional arguments "are subject to change
     *                     for the convenience of memcache developers".
     *
     * @link http://code.google.com/p/memcached/wiki/BinaryProtocolRevamped#Stat
     * @access public
     * @return array  Returns an associative array of server statistics or
     *                FALSE on failure. 
     */
    public function getStats($type = null)
    {
        $this->_send(
            array(
                'opcode' => 0x10,
                'key' => $type,
            )
        );

        $ret = array();
        while (true) {
            $item = $this->_recv();
            if (empty($item['key'])) {
                break;
            }
            $ret[$item['key']] = $item['body'];
        }
        return $ret;
    }

    public function append($key, $value)
    {
        // TODO: If the Memcached::OPT_COMPRESSION is enabled, the operation
        // should failed.
        $sent = $this->_send(array(
                    'opcode' => 0x0e,
                    'key' => $key,
                    'value' => $value,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function prepend($key, $value)
    {
        // TODO: If the Memcached::OPT_COMPRESSION is enabled, the operation
        // should failed.
        $sent = $this->_send(array(
                    'opcode' => 0x0f,
                    'key' => $key,
                    'value' => $value,
                    ));
        $data = $this->_recv();
        if ($data['status'] == 0) {
            return TRUE;
        }

        return FALSE;
    }

    public function getMulti(array $keys)
    {
        // TODO: from http://code.google.com/p/memcached/wiki/BinaryProtocolRevamped#Get,_Get_Quietly,_Get_Key,_Get_Key_Quietly
        //       Clients should implement multi-get (still important for reducing network roundtrips!) as n pipelined requests ...
        $list = array();

        foreach ($keys as $key) {
            $value = $this->get($key);
            if (false !== $value) {
                $list[$key] = $value;
            }
        }

        return $list;
    }


    protected $_options = array();

    public function setOption($key, $value)
    {
	$this->_options[$key] = $value;
    }

    /**
     * Set the memcache object to be a session handler
     *
     * Ex:
     * $m = new MemcacheSASL;
     * $m->addServer('xxx', 11211);
     * $m->setSaslAuthData('user', 'password');
     * $m->setSaveHandler();
     * session_start();
     * $_SESSION['hello'] = 'world';
     *
     * @access public
     * @return void
     */
    public function setSaveHandler()
    {
        session_set_save_handler(
            function($savePath, $sessionName){ // open
            },
            function(){ // close
            },
            function($sessionId){ // read
                return $this->get($sessionId);
            },
            function($sessionId, $data){ // write
                return $this->set($sessionId, $data);
            },
            function($sessionId){ // destroy
                $this->delete($sessionId);
            },
            function($lifetime) { // gc
            }
        );
    }
}
