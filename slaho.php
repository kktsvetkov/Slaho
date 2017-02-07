<?php /**
* Slaho: publish messages in Slack channels by posting to their webhooks
*
* @link https://github.com/kktsvetkov/slaho/
* @license http://opensource.org/licenses/LGPL-3.0 GNU Lesser General Public License, version 3.0
*/
class slaho
{
	/**
	* Webhook to post to, it is generated from your slack registration,
	* "Browse apps > Custom Integrations > Incoming WebHooks > New configuration"
	* @var string Slack webhook, must be like "https://hooks.slack.com/services/T2...avL9k"
	*/
	protected $webhook = '';

	/**
	* Default format values for the posted messages that can be re-used;
	* You can find sample data for these at {@link slaho::$example}
	*
	* @var array key\value pairs of message formatting details
	*/
	protected $default = array();

	/**
	* Constructor: set up the Slack webhook you want to post to; optionally
	* you can also set re-usable default values for message formatting.
	*
	* @param string $webhook Slack webhook, must be like "https://hooks.slack.com/services/T2...avL9k"
	* @param array $default default re-usable message formatting
	* @throws \InvalidArgumentException
	*/
	public function __construct($webhook, array $default = array())
	{
		if (empty($webhook = trim($webhook)))
		{
			throw new \InvalidArgumentException(
				'Empty webhook'
			);
		}
		if (!preg_match('~^https\://hooks\.slack\.com/services/(?:\w|/)+$~', $webhook))
		{
			throw new \InvalidArgumentException(
				'Invalid Slack webhook ' . $webhook
			);
		}
		$this->webhook = $webhook;

		if (!empty($default))
		{
			$this->default = $default;
		}
	}

	/**
	* Post a message to Slack
	*
	* @param string $message
	* @param array $format
	* @uses slaho::post()
	*/
	public function message($message, array $format = array())
	{
		$payload = array(
			'text' => $message
			) + $format + $this->default;

		$this->post(json_encode($payload));
	}

	/**
	* Post the JSON encoded message to Slack webhook
	* @param string $json
	* @throws \BadFunctionCallException
	*/
	public function post($json)
	{
		if (empty(static::$callback))
		{
			if (function_exists('curl_init'))
			{
				static::$callback = array(
					__CLASS__, 'post_curl'
				);
			}
		}

		if (empty(static::$callback))
		{
			if (self::find_curl_bin())
			{
				static::$callback = array(
					__CLASS__, 'post_curl_bin'
				);
			}
		}

		if (empty(static::$callback))
		{
			throw new \BadFunctionCallException(
				'No callback provided for posting to Slack. Use'
					. ' slaho::callback() to set one, the'
					. ' callback takes two arguments: $json'
					. ' string with the message payload,'
					. ' and $url webhook to post to.'
			);
		}

		return call_user_func(
			static::$callback,
			$json,
			$this->webhook
		);
	}

	/**
	* @var callable the callback to use to post the message to Slack; there
	* 	are two built-in methods, one relying on the "curl" extension,
	*	and another using the "curl" binary.
	*/
	protected static $callback;

	/**
	* Set new callback to use to post messages to Slack.
	*
	* The callback takes two arguments: $json string with the message
	* payload, and $url webhook to post to.
	*
	* @param callable $callback
	* @throws \BadFunctionCallException
	*/
	public static function callback($callback)
	{
		if (!is_callable($callback))
		{
			throw new \BadFunctionCallException(
				'Invalid callback'
			);
		}

		self::$callback = $callback;
	}

	/**
	* Post to Slack webhook using the "curl" extension
	* @param string $json
	* @param string $webhook
	*/
	protected static function post_curl($json, $webhook)
	{
		$ch = curl_init($webhook);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'payload=' . $json);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_exec($ch);
	        curl_close($ch);
	}

	/**
	* @var string path to curl binary
	*/
	private static $curl_bin = '';

	/**
	* Look for the curl binary
	*
	* Looks for the "curl" binary in PATH environment, as well as checks
	* whether it is accessible
	*
	* @return string
	*/
	private static function find_curl_bin()
	{
		$p = !empty($_ENV['PATH'])
			? $_ENV['PATH']
			: (!empty($_SERVER['PATH'])
				? $_SERVER['PATH']
				: '/opt/local/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin'
			);
		$PATH = array_unique(explode(PATH_SEPARATOR, $p));
		foreach ($PATH as $i => $Path)
		{
			$seek = $Path . DIRECTORY_SEPARATOR . 'curl';
			if (file_exists($seek))
			{
				return self::$curl_bin = realpath($seek);
			}
		}

		exec('curl -V', $curlOutput);
		if (preg_match('~^curl \d+~', $curlOutput[0]))
		{
			return self::$curl_bin = 'curl';
		}
	}

	/**
	* Post to Slack webhook using the "curl" binary
	* @param string $json
	* @param string $webhook
	*/
	protected static function post_curl_bin($json, $webhook)
	{
		exec(escapeshellcmd(self::$curl_bin)
			. ' -X POST --data-urlencode '
			. escapeshellarg('payload=' . $json)
			. ' ' . escapeshellarg($webhook)
		);
	}

	/**
	* Examples to use as message formatting; use those as templates to
	* build your own formatting out of them
	*
	* @see https://api.slack.com/docs/message-formatting
	* @see https://api.slack.com/docs/message-attachments
	* @see https://api.slack.com/docs/message-guidelines
	*
	* @var array
	*/
	public static $example = array(
		/**
		* simple message, no attachments
		*/
		array(
			/** author of the message */
			'username' => 'Slaho',

			/** author message icon; do not use with "icon_emoji" */
			'icon_url' => 'https://slack.com/img/icons/app-57.png',

			/** author message icon as an emoji; do not use with "icon_url" */
			'icon_emoji' => ':robot_face:',
		),

		/**
		* message with attachments
		*/
		array(
			/** author of the message */
			'username' => 'Slaho',

			/** author message icon; do not use with "icon_emoji" */
			'icon_url' => 'https://slack.com/img/icons/app-57.png',

			/** author message icon as an emoji; do not use with "icon_url" */
			'icon_emoji' => ':space_invader:',

			/** message attachments, try to keep it under 20 attachments, max is 100 attachments */
			'attachments' => array(

				array(
					/**
					* A plain-text summary of the attachment. This text will be used in
					* clients that don't show formatted text (eg. IRC, mobile
					* notifications) and should not contain any markup.
					*/
					'fallback' => 'Required plain-text summary of the attachment.',

					/** optional text, should appear inside the attachment */
					'text' => 'Optional text that appears within the attachment',

					/** optional text, should appear above the attachment */
					'pretext' => 'Optional text that should appear above the formatted data',

					/** optional color, can either be one of 'good', 'warning', 'danger', or any hex color code */
					'color' => '#36a64f',

					/** optional, small text used to display the author's name */
					'author_name' => 'Bobby Tables',

					/** URL for the author_name, will only work if author_name is not empty */
					'author_link' => 'http://flickr.com/bobby/',

					/** URL to a small 16x16px image to be used as an icon for author_name, will only work if author_name is not empty */
					'author_icon' => 'http://flickr.com/icons/bobby.jpg',

					/** optional, attachment title */
					'title' => 'Slack API Documentation',

					/** optional, attachment title URL */
					'title_link' => 'https://api.slack.com/',

					/** optional, list of key\value pairs associated with the attachment */
					'fields' => array(
						array(
							/** field title, cannot contain markup */
							'title' => 'Priority',

							/** field value, can be multi-line */
							'value' => 'High',

							/** how to render the field, as a short block or as a long line */
							'short' => true
						),
						array(
							'title' => 'UUID',
							'value' => '123e4567-e89b-12d3-a456-426655440000',
							'short' => false
						)
				    	),

					/** optional, URL to an image displayed inside a message attachment, supported formats: GIF, JPEG, PNG, and BMP */
					'image_url' => 'http://my-website.com/path/to/image.jpg',

					/** optional, URL to an image displayed aside the attachment, supported formats: GIF, JPEG, PNG, and BMP */
					'thumb_url' => 'http://example.com/path/to/thumb.png',

					/** optional, small text used to identify an attachment, limited to 300 characters */
					'footer' => 'Slack API',

					/** URL to a small 16x16px image to be used as an icon for footer, will only work if footer is not empty */
					'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',

					/** optional, unix timestamp */
					'ts' => 123456789
				),

				array(
					'fallback' => 'Second attachment fallback',

					'text' => 'The Punisher (Frank Castle) is a fictional character appearing in comic books published by <https://en.wikipedia.org/wiki/Marvel_Comics|Marvel Comics>.',
					'pretext' => 'Second attachment',

					'color' => 'danger',
					'author_name' => 'The Punisher',
					'author_link' => 'https://en.wikipedia.org/wiki/Punisher',
					'author_icon' => 'http://icons.iconarchive.com/icons/icons8/windows-8/128/Cinema-Punisher-icon.png',

					'title' => 'Skull SDK Guidelines',
					'title_link' => 'https://skull.com/',

					'fields' => array(
						array(
							'title' => 'Writer',
							'value' => 'Gerry Conway',
							'short' => true
						),
						array(
							'title' => 'Artist',
							'value' => 'John Romita Sr.',
							'short' => true
						)
				    	),

					'image_url' => 'http://vignette1.wikia.nocookie.net/villains/images/b/bc/Skeletor.jpg',
					'thumb_url' => 'http://www.c4charitycars.com/uploads/1/2/9/3/12932633/the-punisher-logo-icon-by-madrapper-d39nuc8.png',
					'footer' => 'Skull SDK',
					'footer_icon' => 'http://icons.iconarchive.com/icons/messbook/outdated/128/Skull-icon.png',
					'ts' => 1486457892
				)
			)
		)
	);

}
