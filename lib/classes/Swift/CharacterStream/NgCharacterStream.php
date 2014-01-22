<?php

/*
 CharacterStream implementation using an array in Swift Mailer.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */



/**
 * A CharacterStream implementation which stores characters in an internal array.
 * @package Swift
 * @subpackage CharacterStream
 * @author Xavier De Cock <xdecock@gmail.com>
 */

Class Swift_CharacterStream_NgCharacterStream
  implements Swift_CharacterStream
{

  /**
   * The char reader (lazy-loaded) for the current charset.
   * @var Swift_CharacterReader
   * @access private
   */
  private $_charReader;

  /**
   * A factory for creatiing CharacterReader instances.
   * @var Swift_CharacterReaderFactory
   * @access private
   */
  private $_charReaderFactory;

  /**
   * The character set this stream is using.
   * @var string
   * @access private
   */
  private $_charset;
  
  /**
   * The datas stored as is
   *
   * @var string
   */
  private $_datas = "";
  
  /**
   * Number of bytes in the stream
   *
   * @var int
   */
  private $_datasSize = 0;
  
  /**
   * Map
   *
   * @var mixed
   */
  private $_map;
  
  /**
   * Map Type
   *
   * @var int
   */
  private $_mapType = 0;
  
  /**
   * Number of characters in the stream
   *
   * @var int
   */
  private $_charCount = 0;
  
  /**
   * Position in the stream
   *
   * @var unknown_type
   */
  private $_currentPos = 0;
  
  /**
   * The constructor
   *
   * @param Swift_CharacterReaderFactory $factory
   * @param unknown_type $charset
   */
  public function __construct(Swift_CharacterReaderFactory $factory,
    $charset)
  {
    $this->setCharacterReaderFactory($factory);
    $this->setCharacterSet($charset);
  }
  
  /* -- Changing parameters of the stream -- */

  /**
   * Set the character set used in this CharacterStream.
   * @param string $charset
   */
  public function setCharacterSet($charset)
  {
    $this->_charset = $charset;
    $this->_charReader = null;
  	$this->_mapType = 0;
  }

  /**
   * Set the CharacterReaderFactory for multi charset support.
   * @param Swift_CharacterReaderFactory $factory
   */
  public function setCharacterReaderFactory(
    Swift_CharacterReaderFactory $factory)
  {
    $this->_charReaderFactory = $factory;
  }

  /**
   * @see Swift_CharacterStream::flushContents()
   *
   */
  public function flushContents()
  {
  	$this->_datas = null;
  	$this->_map = null;
  	$this->_charCount = 0;
  	$this->_currentPos = 0;
  	$this->_datasSize = 0;
  }
  
  /**
   * @see Swift_CharacterStream::importByteStream()
   *
   * @param Swift_OutputByteStream $os
   */
  public function importByteStream(Swift_OutputByteStream $os)
  {
    $this->flushContents();
    $blocks=512;
    $os->setReadPointer(0);
    while(false!==($read = $os->read($blocks)))
      $this->write($read);
  }
  
  /**
   * @see Swift_CharacterStream::importString()
   *
   * @param string $string
   */
  public function importString($string)
  {
    $this->flushContents();
    $this->write($string);
  }
  
	/**
	 * @see Swift_CharacterStream::read()
	 *
	 * @param int $length
	 * @return string
	 */
	public function read($length) {
		//If we're reading with a variable-length (utf8) reader, we really care about bytes,
		// not characters.  Disregard the internal character $map and just return the bytes that matter
		if ($this->_mapType == Swift_CharacterReader::MAP_TYPE_POSITIONS) {
			$length = min($length, $this->_datasSize - $this->_currentPos);
			if ($length <= 0) {
				return false;
			}
			$str = substr($this->_datas, $this->_currentPos, $length);
			$this->_currentPos += $length;
			return $str;
		} else {
			$length = min($length, $this->_charCount - $this->_currentPos);
			if ($length <= 0) {
				return false;
			}
			if ($this->_mapType == Swift_CharacterReader::MAP_TYPE_FIXED_LEN) {
				//Fixed-length byte encoding?  just multiply our fixed length by our offsets..
				$ret = substr($this->_datas, $this->_currentPos * $this->_map, $length * $this->_map);
				$this->_currentPos += $length;
				return $ret;
			//What's an invalid character reader type?
			//I don't know, so I'm not touching this logic with a 10 foot pole, justin case
			} elseif ($this->_mapType == Swift_CharacterReader::MAP_TYPE_INVALID) {
				$ret = '';
				for (; $this->_currentPos < $length; ++$this->_currentPos)
				{
					if (isset ($this->_map[$this->_currentPos]))
					{
						$ret .= '?';
					}
					else
					{
						$ret .= $this->_datas[$this->_currentPos];
					}
				}
			} else {
				return false;
			}
		}
  }
  
	/**
	 * @see Swift_CharacterStream::readBytes()
	 *
	 * @param int $length
	 * @return int[]
	 */
	public function readBytes($length) {
		if ($this->_mapType == Swift_CharacterReader::MAP_TYPE_POSITIONS) {
			$length = min($length, $this->_datasSize - $this->_currentPos);
			if ($length <= 0) {
				return false;
			}
			$ret = unpack('@'.$this->_currentPos.'/C'.$length, $this->_datas);
			$this->_currentPos += $length;
			return $ret;
		} else {
			$read=$this->read($length);
			if ($read!==false) {
				return array_map('ord', str_split($read, 1));
			}
			return false;
		}
	}
  
  /**
   * @see Swift_CharacterStream::setPointer()
   *
   * @param int $charOffset
   */
  public function setPointer($charOffset)
  {
  	if ($this->_charCount<$charOffset){
  		$charOffset=$this->_charCount;
  	}
  	$this->_currentPos = $charOffset;
  }

	/**
	 * @see Swift_CharacterStream::write()
	 *
	 * @param string $chars
	 */
	public function write($chars) {
		//Snag a new character reader, if we don't have one initialized yet
		if (!isset($this->_charReader))
		{
			$this->_charReader = $this->_charReaderFactory->getReaderFor($this->_charset);
			$this->_map = array();
			$this->_mapType = $this->_charReader->getMapType();
		}
		$ignored='';
		$this->_datas .= $chars;
		//If we're doing utf8 encoding, no need to figure out all character encodings, since we adjusted the way
		// that we handle our byte stream in our qp encoder.
		if ($this->_mapType !== Swift_CharacterReader::MAP_TYPE_POSITIONS) {
			$this->_charCount += $this->_charReader->getCharPositions(substr($this->_datas, $this->_datasSize), $this->_datasSize, $this->_map, $ignored);
		}
		if ($ignored !== false) {
			$this->_datasSize=strlen($this->_datas)-strlen($ignored);
		} else {
			$this->_datasSize=strlen($this->_datas);
		}
	}
}