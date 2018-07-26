<?php
namespace SwaggerGen;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class SwaggerRequest implements RequestInterface {

	const METHOD = '';
	const URI = '';

	protected $body;

	protected static $param_names = [];

	protected $headers = [];

	/**
	 * Retrieves the HTTP protocol version as a string.
	 *
	 * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
	 *
	 * @return string HTTP protocol version.
	 */
	public function getProtocolVersion(){
		return 1.1;
	}

	/**
	 * Return an instance with the specified HTTP protocol version.
	 *
	 * The version string MUST contain only the HTTP version number (e.g.,
	 * "1.1", "1.0").
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new protocol version.
	 *
	 * @param string $version HTTP protocol version
	 * @return static
	 */
	public function withProtocolVersion($version){
		return clone $this;
	}

	/**
	 * Retrieves all message header values.
	 *
	 * The keys represent the header name as it will be sent over the wire, and
	 * each value is an array of strings associated with the header.
	 *
	 *     // Represent the headers as a string
	 *     foreach ($message->getHeaders() as $name => $values) {
	 *         echo $name . ": " . implode(", ", $values);
	 *     }
	 *
	 *     // Emit headers iteratively:
	 *     foreach ($message->getHeaders() as $name => $values) {
	 *         foreach ($values as $value) {
	 *             header(sprintf('%s: %s', $name, $value), false);
	 *         }
	 *     }
	 *
	 * While header names are not case-sensitive, getHeaders() will preserve the
	 * exact case in which headers were originally specified.
	 *
	 * @return string[][] Returns an associative array of the message's headers. Each
	 *     key MUST be a header name, and each value MUST be an array of strings
	 *     for that header.
	 */
	public function getHeaders(){
		return $this->headers;
	}

	/**
	 * Checks if a header exists by the given case-insensitive name.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return bool Returns true if any header names match the given header
	 *     name using a case-insensitive string comparison. Returns false if
	 *     no matching header name is found in the message.
	 */
	public function hasHeader($name){
		return isset($this->headers[$name]);
	}

	/**
	 * Retrieves a message header value by the given case-insensitive name.
	 *
	 * This method returns an array of all the header values of the given
	 * case-insensitive header name.
	 *
	 * If the header does not appear in the message, this method MUST return an
	 * empty array.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return string[] An array of string values as provided for the given
	 *    header. If the header does not appear in the message, this method MUST
	 *    return an empty array.
	 */
	public function getHeader($name){
		return $this->headers[$name] ?: [];
	}

	/**
	 * Retrieves a comma-separated string of the values for a single header.
	 *
	 * This method returns all of the header values of the given
	 * case-insensitive header name as a string concatenated together using
	 * a comma.
	 *
	 * NOTE: Not all header values may be appropriately represented using
	 * comma concatenation. For such headers, use getHeader() instead
	 * and supply your own delimiter when concatenating.
	 *
	 * If the header does not appear in the message, this method MUST return
	 * an empty string.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return string A string of values as provided for the given header
	 *    concatenated together using a comma. If the header does not appear in
	 *    the message, this method MUST return an empty string.
	 */
	public function getHeaderLine($name){
		return implode(',', $this->headers[$name] ?: []);
	}

	/**
	 * Return an instance with the provided value replacing the specified header.
	 *
	 * While header names are case-insensitive, the casing of the header will
	 * be preserved by this function, and returned from getHeaders().
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new and/or updated header and value.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws \InvalidArgumentException for invalid header names or values.
	 */
	public function withHeader($name, $value){
		$cloned = clone $this;
		$cloned->setHeader($name, $value);

		return $cloned;
	}

	private function setHeader($name, $value){
		if (!is_array($value)){
			$value = [$value];
		}

		$this->headers[$name] = $value;
	}

	/**
	 * Return an instance with the specified header appended with the given value.
	 *
	 * Existing values for the specified header will be maintained. The new
	 * value(s) will be appended to the existing list. If the header did not
	 * exist previously, it will be added.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new header and/or value.
	 *
	 * @param string $name Case-insensitive header field name to add.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws \InvalidArgumentException for invalid header names or values.
	 */
	public function withAddedHeader($name, $value){
		$cloned = clone $this;
		$cloned->addHeader($name, $value);

		return $cloned;
	}

	private function addHeader($name, $value){
		if (!is_array($value)){
			$value = [$value];
		}
		if (!is_array($this->headers[$name])){
			$this->headers[$name] = [];
		}

		$this->headers[$name] = array_merge($this->headers[$name], $value);
	}

	/**
	 * Return an instance without the specified header.
	 *
	 * Header resolution MUST be done without case-sensitivity.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that removes
	 * the named header.
	 *
	 * @param string $name Case-insensitive header field name to remove.
	 * @return static
	 */
	public function withoutHeader($name){
		return clone $this;
	}

	/**
	 * Gets the body of the message.
	 *
	 * @return StreamInterface Returns the body as a stream.
	 */
	public function getBody(){
		return $this->body;
	}

	/**
	 * Return an instance with the specified message body.
	 *
	 * The body MUST be a StreamInterface object.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return a new instance that has the
	 * new body stream.
	 *
	 * @param StreamInterface $body Body.
	 * @return static
	 * @throws \InvalidArgumentException When the body is not valid.
	 */
	public function withBody(StreamInterface $body){
		return clone $this;
	}

	/**
	 * Retrieves the message's request target.
	 *
	 * Retrieves the message's request-target either as it will appear (for
	 * clients), as it appeared at request (for servers), or as it was
	 * specified for the instance (see withRequestTarget()).
	 *
	 * In most cases, this will be the origin-form of the composed URI,
	 * unless a value was provided to the concrete implementation (see
	 * withRequestTarget() below).
	 *
	 * If no URI is available, and no request-target has been specifically
	 * provided, this method MUST return the string "/".
	 *
	 * @return string
	 */
	public function getRequestTarget(){
		return (string)$this->getUri();
	}

	/**
	 * Return an instance with the specific request-target.
	 *
	 * If the request needs a non-origin-form request-target — e.g., for
	 * specifying an absolute-form, authority-form, or asterisk-form —
	 * this method may be used to create an instance with the specified
	 * request-target, verbatim.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * changed request target.
	 *
	 * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
	 *     request-target forms allowed in request messages)
	 * @param mixed $requestTarget
	 * @return static
	 */
	public function withRequestTarget($requestTarget){
		return clone $this;
	}

	/**
	 * Retrieves the HTTP method of the request.
	 *
	 * @return string Returns the request method.
	 */
	public function getMethod(){
		return static::METHOD;
	}

	/**
	 * Return an instance with the provided HTTP method.
	 *
	 * While HTTP method names are typically all uppercase characters, HTTP
	 * method names are case-sensitive and thus implementations SHOULD NOT
	 * modify the given string.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * changed request method.
	 *
	 * @param string $method Case-sensitive method.
	 * @return static
	 * @throws \InvalidArgumentException for invalid HTTP methods.
	 */
	public function withMethod($method){
		return clone $this;
	}

	/**
	 * Retrieves the URI instance.
	 *
	 * This method MUST return a UriInterface instance.
	 *
	 * @link http://tools.ietf.org/html/rfc3986#section-4.3
	 * @return UriInterface Returns a UriInterface instance
	 *     representing the URI of the request.
	 */
	public function getUri(){
		$uri = static::URI;
		$query = [];
		foreach (static::$param_names as $param_name){
			$query[] = $this->{$param_name};
		}
		if (!empty($query)){
			$uri .= '?'.http_build_query($query);
		}

		return new class($uri) implements UriInterface {
			private $uri;

			public function __construct($uri){
				$this->uri = str_replace(['https://', 'http://'], '', $uri);
			}

			/**
			 * Retrieve the scheme component of the URI.
			 *
			 * If no scheme is present, this method MUST return an empty string.
			 *
			 * The value returned MUST be normalized to lowercase, per RFC 3986
			 * Section 3.1.
			 *
			 * The trailing ":" character is not part of the scheme and MUST NOT be
			 * added.
			 *
			 * @see https://tools.ietf.org/html/rfc3986#section-3.1
			 * @return string The URI scheme.
			 */
			public function getScheme(){
				return 'https';
			}

			/**
			 * Retrieve the authority component of the URI.
			 *
			 * If no authority information is present, this method MUST return an empty
			 * string.
			 *
			 * The authority syntax of the URI is:
			 *
			 * <pre>
			 * [user-info@]host[:port]
			 * </pre>
			 *
			 * If the port component is not set or is the standard port for the current
			 * scheme, it SHOULD NOT be included.
			 *
			 * @see https://tools.ietf.org/html/rfc3986#section-3.2
			 * @return string The URI authority, in "[user-info@]host[:port]" format.
			 */
			public function getAuthority(){
				return '';
			}

			/**
			 * Retrieve the user information component of the URI.
			 *
			 * If no user information is present, this method MUST return an empty
			 * string.
			 *
			 * If a user is present in the URI, this will return that value;
			 * additionally, if the password is also present, it will be appended to the
			 * user value, with a colon (":") separating the values.
			 *
			 * The trailing "@" character is not part of the user information and MUST
			 * NOT be added.
			 *
			 * @return string The URI user information, in "username[:password]" format.
			 */
			public function getUserInfo(){
				return '';
			}

			/**
			 * Retrieve the host component of the URI.
			 *
			 * If no host is present, this method MUST return an empty string.
			 *
			 * The value returned MUST be normalized to lowercase, per RFC 3986
			 * Section 3.2.2.
			 *
			 * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
			 * @return string The URI host.
			 */
			public function getHost(){
				return explode('/', $this->uri)[0];
			}

			/**
			 * Retrieve the port component of the URI.
			 *
			 * If a port is present, and it is non-standard for the current scheme,
			 * this method MUST return it as an integer. If the port is the standard port
			 * used with the current scheme, this method SHOULD return null.
			 *
			 * If no port is present, and no scheme is present, this method MUST return
			 * a null value.
			 *
			 * If no port is present, but a scheme is present, this method MAY return
			 * the standard port for that scheme, but SHOULD return null.
			 *
			 * @return null|int The URI port.
			 */
			public function getPort(){
				return null;
			}

			/**
			 * Retrieve the path component of the URI.
			 *
			 * The path can either be empty or absolute (starting with a slash) or
			 * rootless (not starting with a slash). Implementations MUST support all
			 * three syntaxes.
			 *
			 * Normally, the empty path "" and absolute path "/" are considered equal as
			 * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
			 * do this normalization because in contexts with a trimmed base path, e.g.
			 * the front controller, this difference becomes significant. It's the task
			 * of the user to handle both "" and "/".
			 *
			 * The value returned MUST be percent-encoded, but MUST NOT double-encode
			 * any characters. To determine what characters to encode, please refer to
			 * RFC 3986, Sections 2 and 3.3.
			 *
			 * As an example, if the value should include a slash ("/") not intended as
			 * delimiter between path segments, that value MUST be passed in encoded
			 * form (e.g., "%2F") to the instance.
			 *
			 * @see https://tools.ietf.org/html/rfc3986#section-2
			 * @see https://tools.ietf.org/html/rfc3986#section-3.3
			 * @return string The URI path.
			 */
			public function getPath(){
				$no_host = $this->uriNoHost();

				return explode('?', $no_host)[0];
			}

			private function uriNoHost(){
				$split = explode('/', $this->uri);
				unset($split[0]);

				return implode('/', $split);
			}

			/**
			 * Retrieve the query string of the URI.
			 *
			 * If no query string is present, this method MUST return an empty string.
			 *
			 * The leading "?" character is not part of the query and MUST NOT be
			 * added.
			 *
			 * The value returned MUST be percent-encoded, but MUST NOT double-encode
			 * any characters. To determine what characters to encode, please refer to
			 * RFC 3986, Sections 2 and 3.4.
			 *
			 * As an example, if a value in a key/value pair of the query string should
			 * include an ampersand ("&") not intended as a delimiter between values,
			 * that value MUST be passed in encoded form (e.g., "%26") to the instance.
			 *
			 * @see https://tools.ietf.org/html/rfc3986#section-2
			 * @see https://tools.ietf.org/html/rfc3986#section-3.4
			 * @return string The URI query string.
			 */
			public function getQuery(){
				$no_host = $this->uriNoHost();

				return explode('?', $no_host)[1] ?? '';
			}

			/**
			 * Retrieve the fragment component of the URI.
			 *
			 * If no fragment is present, this method MUST return an empty string.
			 *
			 * The leading "#" character is not part of the fragment and MUST NOT be
			 * added.
			 *
			 * The value returned MUST be percent-encoded, but MUST NOT double-encode
			 * any characters. To determine what characters to encode, please refer to
			 * RFC 3986, Sections 2 and 3.5.
			 *
			 * @see https://tools.ietf.org/html/rfc3986#section-2
			 * @see https://tools.ietf.org/html/rfc3986#section-3.5
			 * @return string The URI fragment.
			 */
			public function getFragment(){
				return '';
			}

			/**
			 * Return an instance with the specified scheme.
			 *
			 * This method MUST retain the state of the current instance, and return
			 * an instance that contains the specified scheme.
			 *
			 * Implementations MUST support the schemes "http" and "https" case
			 * insensitively, and MAY accommodate other schemes if required.
			 *
			 * An empty scheme is equivalent to removing the scheme.
			 *
			 * @param string $scheme The scheme to use with the new instance.
			 * @return static A new instance with the specified scheme.
			 * @throws \InvalidArgumentException for invalid or unsupported schemes.
			 */
			public function withScheme($scheme){
				return clone $this;
			}

			/**
			 * Return an instance with the specified user information.
			 *
			 * This method MUST retain the state of the current instance, and return
			 * an instance that contains the specified user information.
			 *
			 * Password is optional, but the user information MUST include the
			 * user; an empty string for the user is equivalent to removing user
			 * information.
			 *
			 * @param string $user The user name to use for authority.
			 * @param null|string $password The password associated with $user.
			 * @return static A new instance with the specified user information.
			 */
			public function withUserInfo($user, $password = null){
				return clone $this;
			}

			/**
			 * Return an instance with the specified host.
			 *
			 * This method MUST retain the state of the current instance, and return
			 * an instance that contains the specified host.
			 *
			 * An empty host value is equivalent to removing the host.
			 *
			 * @param string $host The hostname to use with the new instance.
			 * @return static A new instance with the specified host.
			 * @throws \InvalidArgumentException for invalid hostnames.
			 */
			public function withHost($host){
				return clone $this;
			}

			/**
			 * Return an instance with the specified port.
			 *
			 * This method MUST retain the state of the current instance, and return
			 * an instance that contains the specified port.
			 *
			 * Implementations MUST raise an exception for ports outside the
			 * established TCP and UDP port ranges.
			 *
			 * A null value provided for the port is equivalent to removing the port
			 * information.
			 *
			 * @param null|int $port The port to use with the new instance; a null value
			 *     removes the port information.
			 * @return static A new instance with the specified port.
			 * @throws \InvalidArgumentException for invalid ports.
			 */
			public function withPort($port){
				return clone $this;
			}

			/**
			 * Return an instance with the specified path.
			 *
			 * This method MUST retain the state of the current instance, and return
			 * an instance that contains the specified path.
			 *
			 * The path can either be empty or absolute (starting with a slash) or
			 * rootless (not starting with a slash). Implementations MUST support all
			 * three syntaxes.
			 *
			 * If the path is intended to be domain-relative rather than path relative then
			 * it must begin with a slash ("/"). Paths not starting with a slash ("/")
			 * are assumed to be relative to some base path known to the application or
			 * consumer.
			 *
			 * Users can provide both encoded and decoded path characters.
			 * Implementations ensure the correct encoding as outlined in getPath().
			 *
			 * @param string $path The path to use with the new instance.
			 * @return static A new instance with the specified path.
			 * @throws \InvalidArgumentException for invalid paths.
			 */
			public function withPath($path){
				return clone $this;
			}

			/**
			 * Return an instance with the specified query string.
			 *
			 * This method MUST retain the state of the current instance, and return
			 * an instance that contains the specified query string.
			 *
			 * Users can provide both encoded and decoded query characters.
			 * Implementations ensure the correct encoding as outlined in getQuery().
			 *
			 * An empty query string value is equivalent to removing the query string.
			 *
			 * @param string $query The query string to use with the new instance.
			 * @return static A new instance with the specified query string.
			 * @throws \InvalidArgumentException for invalid query strings.
			 */
			public function withQuery($query){
				return clone $this;
			}

			/**
			 * Return an instance with the specified URI fragment.
			 *
			 * This method MUST retain the state of the current instance, and return
			 * an instance that contains the specified URI fragment.
			 *
			 * Users can provide both encoded and decoded fragment characters.
			 * Implementations ensure the correct encoding as outlined in getFragment().
			 *
			 * An empty fragment value is equivalent to removing the fragment.
			 *
			 * @param string $fragment The fragment to use with the new instance.
			 * @return static A new instance with the specified fragment.
			 */
			public function withFragment($fragment){
				return clone $this;
			}

			/**
			 * Return the string representation as a URI reference.
			 *
			 * Depending on which components of the URI are present, the resulting
			 * string is either a full URI or relative reference according to RFC 3986,
			 * Section 4.1. The method concatenates the various components of the URI,
			 * using the appropriate delimiters:
			 *
			 * - If a scheme is present, it MUST be suffixed by ":".
			 * - If an authority is present, it MUST be prefixed by "//".
			 * - The path can be concatenated without delimiters. But there are two
			 *   cases where the path has to be adjusted to make the URI reference
			 *   valid as PHP does not allow to throw an exception in __toString():
			 *     - If the path is rootless and an authority is present, the path MUST
			 *       be prefixed by "/".
			 *     - If the path is starting with more than one "/" and no authority is
			 *       present, the starting slashes MUST be reduced to one.
			 * - If a query is present, it MUST be prefixed by "?".
			 * - If a fragment is present, it MUST be prefixed by "#".
			 *
			 * @see http://tools.ietf.org/html/rfc3986#section-4.1
			 * @return string
			 */
			public function __toString(){
				return $this->getScheme().'://'.$this->uri;
			}
		};
	}

	/**
	 * Returns an instance with the provided URI.
	 *
	 * This method MUST update the Host header of the returned request by
	 * default if the URI contains a host component. If the URI does not
	 * contain a host component, any pre-existing Host header MUST be carried
	 * over to the returned request.
	 *
	 * You can opt-in to preserving the original state of the Host header by
	 * setting `$preserveHost` to `true`. When `$preserveHost` is set to
	 * `true`, this method interacts with the Host header in the following ways:
	 *
	 * - If the Host header is missing or empty, and the new URI contains
	 *   a host component, this method MUST update the Host header in the returned
	 *   request.
	 * - If the Host header is missing or empty, and the new URI does not contain a
	 *   host component, this method MUST NOT update the Host header in the returned
	 *   request.
	 * - If a Host header is present and non-empty, this method MUST NOT update
	 *   the Host header in the returned request.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new UriInterface instance.
	 *
	 * @link http://tools.ietf.org/html/rfc3986#section-4.3
	 * @param UriInterface $uri New request URI to use.
	 * @param bool $preserveHost Preserve the original state of the Host header.
	 * @return static
	 */
	public function withUri(UriInterface $uri, $preserveHost = false){
		return clone $this;
	}
}
