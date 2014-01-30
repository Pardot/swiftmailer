<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Handles Quoted Printable (QP) Encoding in Swift Mailer.
 *
 * Possibly the most accurate RFC 2045 QP implementation found in PHP.
 *
 * @package    Swift
 * @subpackage Encoder
 * @author     Chris Corbyn
 */
class Swift_Encoder_QpEncoder implements Swift_Encoder
{
	const LF = 10;
	const CR = 13;

	/**
     * The CharacterStream used for reading characters (as opposed to bytes).
     *
     * @var Swift_CharacterStream
     */
    protected $_charStream;

    /**
     * A filter used if input should be canonicalized.
     *
     * @var Swift_StreamFilter
     */
    protected $_filter;

    /**
     * Pre-computed QP for HUGE optimization.
     *
     * @var string[]
     */
    protected static $_qpMap = array(
        0   => '=00', 1   => '=01', 2   => '=02', 3   => '=03', 4   => '=04',
        5   => '=05', 6   => '=06', 7   => '=07', 8   => '=08', 9   => '=09',
        10  => '=0A', 11  => '=0B', 12  => '=0C', 13  => '=0D', 14  => '=0E',
        15  => '=0F', 16  => '=10', 17  => '=11', 18  => '=12', 19  => '=13',
        20  => '=14', 21  => '=15', 22  => '=16', 23  => '=17', 24  => '=18',
        25  => '=19', 26  => '=1A', 27  => '=1B', 28  => '=1C', 29  => '=1D',
        30  => '=1E', 31  => '=1F', 32  => '=20', 33  => '=21', 34  => '=22',
        35  => '=23', 36  => '=24', 37  => '=25', 38  => '=26', 39  => '=27',
        40  => '=28', 41  => '=29', 42  => '=2A', 43  => '=2B', 44  => '=2C',
        45  => '=2D', 46  => '=2E', 47  => '=2F', 48  => '=30', 49  => '=31',
        50  => '=32', 51  => '=33', 52  => '=34', 53  => '=35', 54  => '=36',
        55  => '=37', 56  => '=38', 57  => '=39', 58  => '=3A', 59  => '=3B',
        60  => '=3C', 61  => '=3D', 62  => '=3E', 63  => '=3F', 64  => '=40',
        65  => '=41', 66  => '=42', 67  => '=43', 68  => '=44', 69  => '=45',
        70  => '=46', 71  => '=47', 72  => '=48', 73  => '=49', 74  => '=4A',
        75  => '=4B', 76  => '=4C', 77  => '=4D', 78  => '=4E', 79  => '=4F',
        80  => '=50', 81  => '=51', 82  => '=52', 83  => '=53', 84  => '=54',
        85  => '=55', 86  => '=56', 87  => '=57', 88  => '=58', 89  => '=59',
        90  => '=5A', 91  => '=5B', 92  => '=5C', 93  => '=5D', 94  => '=5E',
        95  => '=5F', 96  => '=60', 97  => '=61', 98  => '=62', 99  => '=63',
        100 => '=64', 101 => '=65', 102 => '=66', 103 => '=67', 104 => '=68',
        105 => '=69', 106 => '=6A', 107 => '=6B', 108 => '=6C', 109 => '=6D',
        110 => '=6E', 111 => '=6F', 112 => '=70', 113 => '=71', 114 => '=72',
        115 => '=73', 116 => '=74', 117 => '=75', 118 => '=76', 119 => '=77',
        120 => '=78', 121 => '=79', 122 => '=7A', 123 => '=7B', 124 => '=7C',
        125 => '=7D', 126 => '=7E', 127 => '=7F', 128 => '=80', 129 => '=81',
        130 => '=82', 131 => '=83', 132 => '=84', 133 => '=85', 134 => '=86',
        135 => '=87', 136 => '=88', 137 => '=89', 138 => '=8A', 139 => '=8B',
        140 => '=8C', 141 => '=8D', 142 => '=8E', 143 => '=8F', 144 => '=90',
        145 => '=91', 146 => '=92', 147 => '=93', 148 => '=94', 149 => '=95',
        150 => '=96', 151 => '=97', 152 => '=98', 153 => '=99', 154 => '=9A',
        155 => '=9B', 156 => '=9C', 157 => '=9D', 158 => '=9E', 159 => '=9F',
        160 => '=A0', 161 => '=A1', 162 => '=A2', 163 => '=A3', 164 => '=A4',
        165 => '=A5', 166 => '=A6', 167 => '=A7', 168 => '=A8', 169 => '=A9',
        170 => '=AA', 171 => '=AB', 172 => '=AC', 173 => '=AD', 174 => '=AE',
        175 => '=AF', 176 => '=B0', 177 => '=B1', 178 => '=B2', 179 => '=B3',
        180 => '=B4', 181 => '=B5', 182 => '=B6', 183 => '=B7', 184 => '=B8',
        185 => '=B9', 186 => '=BA', 187 => '=BB', 188 => '=BC', 189 => '=BD',
        190 => '=BE', 191 => '=BF', 192 => '=C0', 193 => '=C1', 194 => '=C2',
        195 => '=C3', 196 => '=C4', 197 => '=C5', 198 => '=C6', 199 => '=C7',
        200 => '=C8', 201 => '=C9', 202 => '=CA', 203 => '=CB', 204 => '=CC',
        205 => '=CD', 206 => '=CE', 207 => '=CF', 208 => '=D0', 209 => '=D1',
        210 => '=D2', 211 => '=D3', 212 => '=D4', 213 => '=D5', 214 => '=D6',
        215 => '=D7', 216 => '=D8', 217 => '=D9', 218 => '=DA', 219 => '=DB',
        220 => '=DC', 221 => '=DD', 222 => '=DE', 223 => '=DF', 224 => '=E0',
        225 => '=E1', 226 => '=E2', 227 => '=E3', 228 => '=E4', 229 => '=E5',
        230 => '=E6', 231 => '=E7', 232 => '=E8', 233 => '=E9', 234 => '=EA',
        235 => '=EB', 236 => '=EC', 237 => '=ED', 238 => '=EE', 239 => '=EF',
        240 => '=F0', 241 => '=F1', 242 => '=F2', 243 => '=F3', 244 => '=F4',
        245 => '=F5', 246 => '=F6', 247 => '=F7', 248 => '=F8', 249 => '=F9',
        250 => '=FA', 251 => '=FB', 252 => '=FC', 253 => '=FD', 254 => '=FE',
        255 => '=FF'
        );

    protected static $_safeMapShare = array();

    /**
     * A map of non-encoded ascii characters.
     *
     * @var string[]
     */
    protected $_safeMap = array();

    /**
     * Creates a new QpEncoder for the given CharacterStream.
     *
     * @param Swift_CharacterStream $charStream to use for reading characters
     * @param Swift_StreamFilter    $filter     if input should be canonicalized
     */
    public function __construct(Swift_CharacterStream $charStream, Swift_StreamFilter $filter = null)
    {
        $this->_charStream = $charStream;
        if (!isset(self::$_safeMapShare[$this->getSafeMapShareId()])) {
            $this->initSafeMap();
            self::$_safeMapShare[$this->getSafeMapShareId()] = $this->_safeMap;
        } else {
            $this->_safeMap = self::$_safeMapShare[$this->getSafeMapShareId()];
        }
        $this->_filter = $filter;
    }

    public function __sleep()
    {
        return array('_charStream', '_filter');
    }

    public function __wakeup()
    {
        if (!isset(self::$_safeMapShare[$this->getSafeMapShareId()])) {
            $this->initSafeMap();
            self::$_safeMapShare[$this->getSafeMapShareId()] = $this->_safeMap;
        } else {
            $this->_safeMap = self::$_safeMapShare[$this->getSafeMapShareId()];
        }
    }

    protected function getSafeMapShareId()
    {
        return get_class($this);
    }

    protected function initSafeMap()
    {
        foreach (array_merge(
            array(0x09, 0x20), range(0x21, 0x3C), range(0x3E, 0x7E)) as $byte)
        {
            $this->_safeMap[$byte] = chr($byte);
        }
    }

    /**
     * Takes an unencoded string and produces a QP encoded string from it.
     *
     * QP encoded strings have a maximum line length of 76 characters.
     * If the first line needs to be shorter, indicate the difference with
     * $firstLineOffset.
     *
     * @param string  $string to encode
     * @param integer $firstLineOffset, optional
     * @param integer $maxLineLength,   optional 0 indicates the default of 76 chars
     *
     * @return string
     */
	public function encodeString($string, $firstLineOffset = 0,
								 $maxLineLength = 0, $ignoreMultibyteCharacters = true)
	{
		$startMicros = microtime(true);
		if ($maxLineLength > 76 || $maxLineLength <= 0)
		{
			$maxLineLength = 76;
		}
		//Reduce everything to \n, and then filter below
		$string = str_replace("\r\n", "\n", $string);
		$string = str_replace("\r", "\n", $string);
		while (strpos($string, " \n") !== false) {
			$string = str_replace(" \n", "\n", $string);
		}

		$lineLen = $firstLineOffset;
		$lines = array();
		$currentLine = '';
		$prevSize = $size = 0;
		$prevChar = $char = '';

		$this->_charStream->flushContents();
		$this->_charStream->importString($string);

		$multibytePiece = "";
		$multibyteLength = 0;
		$multibyteCurrentLength = 0;

		//Since we filter our bytes manually to split them across lines below, we can use arbitrary batch sizes here.
		//Mild testing suggests that a chunk size between 200 and 1,000 is optimal for unpack()
		while (false !== $bytes = $this->_nextSequence(1000))
		{
			$skippedCR = false;
			foreach ($bytes as $b) {
				//if we've found a \r\n, shove those back in place, set the current line/size, and move on
				//Nothing fancy here, MIME standards say you cr/lf your newlines
				if ($b == self::LF || $skippedCR) {
					$skippedCR = false;
					$lineLen = 0;
					//verify wrapping shenanigans now
					if ($lineLen + $prevSize > $maxLineLength) {
						$lines []= $currentLine;
						$currentLine = $prevChar;
					} else {
						$currentLine .= $prevChar;
					}
					$currentLine .= "\r\n";
					$prevSize = 0;
					$prevChar = '';
					//If we're on a \n, skip onward.  If we're not, then just the previous char was a CR,
					// and thus we want to shove our \r\n into place and then continue processing the current byte
					if ($b == self::LF) {
						continue;
					}
				}

				if ($b == self::CR) {
					$skippedCR = true;
					//do nothing.  These are incorrectly added by our filter, and get replaced directly below
					continue;
				}

				/**
				 * We can't append our bytes until we're sure that we're appending an entire valid character
				 *  to the string in one go.  For multiline SMTP fields, each field is supposed to be self-sufficient.
				 *  See http://tools.ietf.org/html/rfc2047 - "Each 'encoded-word' MUST encode an integral number of octets."
				 */
				//If we don't have a multibyte length yet..
				if (!$multibyteLength) {
					//If we're in the safe zone, flag our character and size, and move on
					if (isset($this->_safeMap[$b])) {
						$char = $this->_safeMap[$b];
						$size = 1;
					} else {
						/**
						 *  If we're in the danger zone, find out our multibyte length
						 *  Valid single-bytes are contained in _safeMap
						 *  Valid double-bytes start with C2->DF
						 *  Valid triple-bytes start with E0 -> EF
						 *  Otherwise, assume 4
						 *  http://en.wikipedia.org/wiki/Unicode
						 */
						if ($b <= 126) { //126 = 7E = 1byte max
							$multibyteLength = 1;
						} elseif ($b <= 223) { //223 = DF = 2byte max
							$multibyteLength = 2;
						} elseif ($b <= 239) {//239 = EF = 3byte max
							$multibyteLength = 3;
						} else {
							$multibyteLength = 4;
						}
						$multibyteCurrentLength = 0;
						$multibytePiece = "";
					}
				}
				if ($multibyteLength) {
					$multibytePiece .= $this->_qpMap[$b];
					$multibyteCurrentLength++;

					//If we're at the end of our utf8 string..
					if ($multibyteCurrentLength == $multibyteLength || $ignoreMultibyteCharacters) {
						//set our size and char accordingly
						$size = $multibyteCurrentLength * 3;
						$char = $multibytePiece;
						//and reset our multibyte length
						$multibyteLength = 0;
					} else {
						//Otherwise, move on to the next byte and see where that gets us
						continue;
					}
				}


				//verify the size on each loop iteration for speedz
				//If we made it here, the next char is not a \n, so we will need to wrap
				if ($lineLen + $prevSize >= $maxLineLength) {
					$lines []= $currentLine;
					$currentLine = $prevChar;
					$lineLen = $prevSize;
				} else {
					$lineLen += $prevSize;
					$currentLine .= $prevChar;
				}

				//stash old values at the end of each iteration
				$prevChar = $char;
				$prevSize = $size;
			}
		}
		if ($prevSize && $prevChar !== '') {
			$currentLine .= $prevChar;
		}

		if ($currentLine !== '') {
			$lines []= $currentLine;
		}

		$encodedResult = $this->_standardize(implode("=\r\n", $lines));

		$endMicros = microtime(true);

		if (class_exists('GraphiteClient', false)) {
			GraphiteClient::timing('email.encode', ($endMicros - $startMicros) * 1000, 0.1);
		}

		return $encodedResult;
	}

    /**
     * Updates the charset used.
     *
     * @param string $charset
     */
    public function charsetChanged($charset)
    {
        $this->_charStream->setCharacterSet($charset);
    }

    // -- Protected methods

    /**
     * Encode the given byte array into a verbatim QP form.
     *
     * @param integer[] $bytes
     * @param integer   $size
     *
     * @return string
     */
    protected function _encodeByteSequence(array $bytes, &$size)
    {
        $ret = '';
        $size=0;
        foreach ($bytes as $b) {
            if (isset($this->_safeMap[$b])) {
                $ret .= $this->_safeMap[$b];
                ++$size;
            } else {
                $ret .= self::$_qpMap[$b];
                $size+=3;
            }
        }

        return $ret;
    }

    /**
     * Get the next sequence of bytes to read from the char stream.
     *
     * @param integer $size number of bytes to read
     *
     * @return integer[]
     */
    protected function _nextSequence($size = 4)
    {
        return $this->_charStream->readBytes($size);
    }

    /**
     * Make sure CRLF is correct and HT/SPACE are in valid places.
     *
     * @param string $string
     *
     * @return string
     */
    protected function _standardize($string)
    {
        $string = str_replace(array("\t=0D=0A", " =0D=0A", "=0D=0A"),
            array("=09\r\n", "=20\r\n", "\r\n"), $string
            );
        switch ($end = ord(substr($string, -1))) {
            case 0x09:
            case 0x20:
                $string = substr_replace($string, self::$_qpMap[$end], -1);
        }

        return $string;
    }
}
