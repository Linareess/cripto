<?php
/**
 * ANGIE - The site restoration script for backup archives created by Akeeba Backup and Akeeba Solo
 *
 * @package   angie
 * @copyright Copyright (c)2009-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die;

/**
 * IP address helper
 *
 * Makes sure that we get the real IP of the user
 */
class AUtilsIp
{
	/**
	 * The IP address of the current visitor
	 *
	 * @var   string
	 */
	protected static $ip = null;

	/**
	 * Should I allow IP overrides through X-Forwarded-For or Client-Ip HTTP headers?
	 *
	 * @var    bool
	 */
	protected static $allowIpOverrides = true;

	/**
	 * See self::detectAndCleanIP and setUseFirstIpInChain
	 *
	 * If this is enabled (default) self::detectAndCleanIP will return the FIRST IP in case there is an IP chaing coming
	 * for example from an X-Forwarded-For HTTP header. When set to false it will simulate the old behavior in FOF up to
	 * and including 3.1.1 which returned the LAST IP in the list.
	 *
	 * @var   bool
	 */
	protected static $useFirstIpInChain = true;

	/**
	 * Set the $useFirstIpInChain flag. See above.
	 *
	 * @param   bool  $value
	 */
	public static function setUseFirstIpInChain($value = true)
	{
		self::$useFirstIpInChain = $value;
	}

	/**
	 * Get the current visitor's IP address
	 *
	 * @return string
	 */
	public static function getUserIP()
	{
		if (is_null(static::$ip))
		{
			$ip = self::detectAndCleanIP();

			if (!empty($ip) && ($ip != '0.0.0.0') && function_exists('inet_pton') && function_exists('inet_ntop'))
			{
				$myIP = @inet_pton($ip);

				if ($myIP !== false)
				{
					$ip = inet_ntop($myIP);
				}
			}

			static::setIp($ip);
		}

		return static::$ip;
	}

	/**
	 * Set the IP address of the current visitor
	 *
	 * @param   string  $ip
	 *
	 * @return  void
	 */
	public static function setIp($ip)
	{
		static::$ip = $ip;
	}

	/**
	 * Is it an IPv6 IP address?
	 *
	 * @param   string   $ip  An IPv4 or IPv6 address
	 *
	 * @return  boolean  True if it's IPv6
	 */
	protected static function isIPv6($ip)
	{
		if (strstr($ip, ':'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks if an IP is contained in a list of IPs or IP expressions
	 *
	 * @param   string        $ip       The IPv4/IPv6 address to check
	 * @param   array|string  $ipTable  An IP expression (or a comma-separated or array list of IP expressions) to check against
	 *
	 * @return  null|boolean  True if it's in the list
	 */
	public static function IPinList($ip, $ipTable = '')
	{
		// No point proceeding with an empty IP list
		if (empty($ipTable))
		{
			return false;
		}

		// If the IP list is not an array, convert it to an array
		if (!is_array($ipTable))
		{
			if (strpos($ipTable, ',') !== false)
			{
				$ipTable = explode(',', $ipTable);
				$ipTable = array_map(function($x) { return trim($x); }, $ipTable);
			}
			else
			{
				$ipTable = trim($ipTable);
				$ipTable = array($ipTable);
			}
		}

		// If no IP address is found, return false
		if ($ip == '0.0.0.0')
		{
			return false;
		}

		// If no IP is given, return false
		if (empty($ip))
		{
			return false;
		}

		// Sanity check
		if (!function_exists('inet_pton'))
		{
			return false;
		}

		// Get the IP's in_adds representation
		$myIP = @inet_pton($ip);

		// If the IP is in an unrecognisable format, quite
		if ($myIP === false)
		{
			return false;
		}

		$ipv6 = self::isIPv6($ip);

		foreach ($ipTable as $ipExpression)
		{
			$ipExpression = trim($ipExpression);

			// Inclusive IP range, i.e. 123.123.123.123-124.125.126.127
			if (strstr($ipExpression, '-'))
			{
				list($from, $to) = explode('-', $ipExpression, 2);

				if ($ipv6 && (!self::isIPv6($from) || !self::isIPv6($to)))
				{
					// Do not apply IPv4 filtering on an IPv6 address
					continue;
				}
				elseif (!$ipv6 && (self::isIPv6($from) || self::isIPv6($to)))
				{
					// Do not apply IPv6 filtering on an IPv4 address
					continue;
				}

				$from = @inet_pton(trim($from));
				$to = @inet_pton(trim($to));

				// Sanity check
				if (($from === false) || ($to === false))
				{
					continue;
				}

				// Swap from/to if they're in the wrong order
				if ($from > $to)
				{
					list($from, $to) = array($to, $from);
				}

				if (($myIP >= $from) && ($myIP <= $to))
				{
					return true;
				}
			}
			// Netmask or CIDR provided
			elseif (strstr($ipExpression, '/'))
			{
				$binaryip = self::inet_to_bits($myIP);

				list($net, $maskbits) = explode('/', $ipExpression, 2);
				if ($ipv6 && !self::isIPv6($net))
				{
					// Do not apply IPv4 filtering on an IPv6 address
					continue;
				}
				elseif (!$ipv6 && self::isIPv6($net))
				{
					// Do not apply IPv6 filtering on an IPv4 address
					continue;
				}
				elseif ($ipv6 && strstr($maskbits, ':'))
				{
					// Perform an IPv6 CIDR check
					if (self::checkIPv6CIDR($myIP, $ipExpression))
					{
						return true;
					}

					// If we didn't match it proceed to the next expression
					continue;
				}
				elseif (!$ipv6 && strstr($maskbits, '.'))
				{
					// Convert IPv4 netmask to CIDR
					$long = ip2long($maskbits);
					$base = ip2long('255.255.255.255');
					$maskbits = 32 - log(($long ^ $base) + 1, 2);
				}

				// Convert network IP to in_addr representation
				$net = @inet_pton($net);

				// Sanity check
				if ($net === false)
				{
					continue;
				}

				// Get the network's binary representation
				$binarynet = self::inet_to_bits($net);
				$expectedNumberOfBits = $ipv6 ? 128 : 24;
				$binarynet = str_pad($binarynet, $expectedNumberOfBits, '0', STR_PAD_RIGHT);

				// Check the corresponding bits of the IP and the network
				$ip_net_bits = substr($binaryip, 0, $maskbits);
				$net_bits = substr($binarynet, 0, $maskbits);

				if ($ip_net_bits == $net_bits)
				{
					return true;
				}
			}
			else
			{
				// IPv6: Only single IPs are supported
				if ($ipv6)
				{
					$ipExpression = trim($ipExpression);

					if (!self::isIPv6($ipExpression))
					{
						continue;
					}

					$ipCheck = @inet_pton($ipExpression);
					if ($ipCheck === false)
					{
						continue;
					}

					if ($ipCheck == $myIP)
					{
						return true;
					}
				}
				else
				{
					// Standard IPv4 address, i.e. 123.123.123.123 or partial IP address, i.e. 123.[123.][123.][123]
					$dots = 0;
					if (substr($ipExpression, -1) == '.')
					{
						// Partial IP address. Convert to CIDR and re-match
						foreach (count_chars($ipExpression, 1) as $i => $val)
						{
							if ($i == 46)
							{
								$dots = $val;
							}
						}

						$netmask = '255.255.255.255';

						switch ($dots)
						{
							case 1:
								$netmask = '255.0.0.0';
								$ipExpression .= '0.0.0';
								break;

							case 2:
								$netmask = '255.255.0.0';
								$ipExpression .= '0.0';
								break;

							case 3:
								$netmask = '255.255.255.0';
								$ipExpression .= '0';
								break;

							default:
								$dots = 0;
						}

						if ($dots)
						{
							$binaryip = self::inet_to_bits($myIP);

							// Convert netmask to CIDR
							$long = ip2long($netmask);
							$base = ip2long('255.255.255.255');
							$maskbits = 32 - log(($long ^ $base) + 1, 2);

							$net = @inet_pton($ipExpression);

							// Sanity check
							if ($net === false)
							{
								continue;
							}

							// Get the network's binary representation
							$binarynet = self::inet_to_bits($net);
							$expectedNumberOfBits = $ipv6 ? 128 : 24;
							$binarynet = str_pad($binarynet, $expectedNumberOfBits, '0', STR_PAD_RIGHT);

							// Check the corresponding bits of the IP and the network
							$ip_net_bits = substr($binaryip, 0, $maskbits);
							$net_bits = substr($binarynet, 0, $maskbits);

							if ($ip_net_bits == $net_bits)
							{
								return true;
							}
						}
					}
					if (!$dots)
					{
						$ip = @inet_pton(trim($ipExpression));
						if ($ip == $myIP)
						{
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Works around the REMOTE_ADDR not containing the user's IP
	 */
	public static function workaroundIPIssues()
	{
		$ip = self::getUserIP();

		if (array_key_exists('REMOTE_ADDR', $_SERVER) && ($_SERVER['REMOTE_ADDR'] == $ip))
		{
			return;
		}

		if (array_key_exists('REMOTE_ADDR', $_SERVER))
		{
			$_SERVER['FOF_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
		}
		elseif (function_exists('getenv'))
		{
			if (getenv('REMOTE_ADDR'))
			{
				$_SERVER['FOF_REMOTE_ADDR'] = getenv('REMOTE_ADDR');
			}
		}

		$_SERVER['REMOTE_ADDR'] = $ip;
	}

	/**
	 * Should I allow the remote client's IP to be overridden by an X-Forwarded-For or Client-Ip HTTP header?
	 *
	 * @param   bool  $newState  True to allow the override
	 *
	 * @return  void
	 */
	public static function setAllowIpOverrides($newState)
	{
		self::$allowIpOverrides = $newState ? true : false;
	}

	/**
	 * Gets the visitor's IP address. Automatically handles reverse proxies
	 * reporting the IPs of intermediate devices, like load balancers. Examples:
	 * https://www.akeeba.com/support/admin-tools/13743-double-ip-adresses-in-security-exception-log-warnings.html
	 * http://stackoverflow.com/questions/2422395/why-is-request-envremote-addr-returning-two-ips
	 * The solution used is assuming that the first IP address is the external one (unless $useFirstIpInChain is set to false)
	 *
	 * @return  string
	 */
	protected static function detectAndCleanIP()
	{
		$ip = static::detectIP();

		if ((strstr($ip, ',') !== false) || (strstr($ip, ' ') !== false))
		{
			$ip = str_replace(' ', ',', $ip);
			$ip = str_replace(',,', ',', $ip);
			$ips = explode(',', $ip);
			$ip = '';

			// Loop until we're running out of parts or we have a hit
			while ($ips)
			{
				$ip = array_shift($ips);
				$ip = trim($ip);

				if (self::$useFirstIpInChain)
				{
					return self::cleanIP($ip);
				}
			}
		}

		return self::cleanIP($ip);
	}

	protected static function cleanIP($ip)
	{
		$ip = trim($ip);

		/**
		 * Work around IPv6/IPv4 address embedding.
		 *
		 * IPv4 addresses may be embedded in an IPv6 address. This is always 80 zeroes, 16 ones and the IPv4 address.
		 * In IPv6 notations this is 0:0:0:0:0:FFFF:192.168.1.1, or ::FFFF:192.168.1.1
		 *
		 * @see http://www.tcpipguide.com/free/t_IPv6IPv4AddressEmbedding-2.htm
		 */
		if ((strpos($ip, '::') === 0) && (strstr($ip, '.') !== false))
		{
			$ip = substr($ip, strrpos($ip, ':') + 1);
		}
		elseif ((strpos(strtoupper($ip), ':FFFF:') !== false) && (strstr($ip, '.') !== false))
		{
			$ip = substr($ip, strrpos($ip, ':') + 1);
		}

		return $ip;
	}

	/**
	 * Gets the visitor's IP address
	 *
	 * @return  string
	 */
	protected static function detectIP()
	{
		// Normally the $_SERVER superglobal is set
		if (isset($_SERVER))
		{
			// Do we have an x-forwarded-for HTTP header (e.g. NginX)?
			if (self::$allowIpOverrides && array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
			{
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}

			// Are we under CloudFlare?
			if (self::$allowIpOverrides && array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER))
			{
				return $_SERVER['HTTP_CF_CONNECTING_IP'];
			}

			// Are we using Sucuri firewall? They use a custom HTTP header
			if (self::$allowIpOverrides && array_key_exists('HTTP_X_SUCURI_CLIENTIP', $_SERVER))
			{
				return $_SERVER['HTTP_X_SUCURI_CLIENTIP'];
			}

			// Do we have a client-ip header (e.g. non-transparent proxy)?
			if (self::$allowIpOverrides && array_key_exists('HTTP_CLIENT_IP', $_SERVER))
			{
				return $_SERVER['HTTP_CLIENT_IP'];
			}

			// CLI applications
			if (!array_key_exists('REMOTE_ADDR', $_SERVER))
			{
				return '';
			}

			// Normal, non-proxied server or server behind a transparent proxy
			return $_SERVER['REMOTE_ADDR'];
		}

		// This part is executed on PHP running as CGI, or on SAPIs which do
		// not set the $_SERVER superglobal
		// If getenv() is disabled, you're screwed
		if (!function_exists('getenv'))
		{
			return '';
		}

		// Do we have an x-forwarded-for HTTP header?
		if (self::$allowIpOverrides && getenv('HTTP_X_FORWARDED_FOR'))
		{
			return getenv('HTTP_X_FORWARDED_FOR');
		}

		// Are we under CloudFlare?
		if (self::$allowIpOverrides && getenv('HTTP_CF_CONNECTING_IP'))
		{
			return getenv('HTTP_CF_CONNECTING_IP');
		}

		// Are we using Sucuri firewall? They use a custom HTTP header
		if (self::$allowIpOverrides && getenv('HTTP_X_SUCURI_CLIENTIP'))
		{
			return getenv('HTTP_X_SUCURI_CLIENTIP');
		}

		// Do we have a client-ip header?
		if (self::$allowIpOverrides && getenv('HTTP_CLIENT_IP'))
		{
			return getenv('HTTP_CLIENT_IP');
		}

		// Normal, non-proxied server or server behind a transparent proxy
		if (getenv('REMOTE_ADDR'))
		{
			return getenv('REMOTE_ADDR');
		}

		// Catch-all case for broken servers and CLI applications
		return '';
	}

	/**
	 * Converts inet_pton output to bits string
	 *
	 * @param   string $inet The in_addr representation of an IPv4 or IPv6 address
	 *
	 * @return  string
	 */
	protected static function inet_to_bits($inet)
	{
		if (strlen($inet) == 4)
		{
			$unpacked = unpack('C4', $inet);
		}
		else
		{
			$unpacked = unpack('C16', $inet);
		}

		$binaryip = '';

		foreach ($unpacked as $byte)
		{
			$binaryip .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
		}

		return $binaryip;
	}

	/**
	 * Checks if an IPv6 address $ip is part of the IPv6 CIDR block $cidrnet
	 *
	 * @param   string  $ip       The IPv6 address to check, e.g. 21DA:00D3:0000:2F3B:02AC:00FF:FE28:9C5A
	 * @param   string  $cidrnet  The IPv6 CIDR block, e.g. 21DA:00D3:0000:2F3B::/64
	 *
	 * @return  bool
	 */
	protected static function checkIPv6CIDR($ip, $cidrnet)
	{
		$ip = inet_pton($ip);
		$binaryip=self::inet_to_bits($ip);

		list($net,$maskbits)=explode('/',$cidrnet);
		$net=inet_pton($net);
		$binarynet=self::inet_to_bits($net);

		$ip_net_bits=substr($binaryip,0,$maskbits);
		$net_bits   =substr($binarynet,0,$maskbits);

		return $ip_net_bits === $net_bits;
	}
}
