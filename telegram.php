<?php
/**
 * WordPress to Telegrambot
 *
 * Description: Read RSS and send to Telegram
 * Author: Sean <sean@sean.taipei>
 * Author URI: https://sean.taipei/
 * Version: 0.1
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @author      Sean <sean@sean.taipei>
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt
 * @link        Github Repo: https://github.com/kocpc/Telegram-RSS-feed-bot
 * @since       0.1
 */

require('config.php');

$xml = file_get_contents(RSS_URL);
$object = simplexml_load_string($xml);  # XML to Object (will lose some data)
$json = json_encode($object);  # Object to JSON
$data = json_decode($json, True);  # JSON to Array
$item = $data['channel']['item'][0];  # Latest article
$url = $item['link'];

$pubtime = $item['pubDate'];  # publish date
$pubtime = strtotime($pubtime);  # Convert to unix timestamp
$late = time() - $pubtime;

if ($late > Rate*60)  # second*60 = minute
	exit;  # No new article

$data = file_get_contents($url);

preg_match('/" rel="author">(.*?)<\/a><\/span>/', $data, $matches);
$author = $matches[1];
$author = str_replace(' ', '_', $author);

$msg .= '<b>' . enHTML($item['title']) . '</b>  #' . enHTML($author) . "\n";   # Like this: <bold>Helo World</bold>  #Sean

if (preg_match('/<meta name="keywords" content="(.+?)"\/>/', $data, $matches)) {
	$keywords = $matches[1];
	$keywords = str_replace(', ', ',', $keywords);
	$keywords = str_replace(' ', '_', $keywords);
	$keywords = str_replace('-', '_', $keywords);
	$msg .= 'Tags: #' . str_replace(',', ' #', $keywords) . "\n";
}

if (preg_match('/<meta name="description" content="(.+?)" *\/>/s', $data, $matches))
	$msg .= $matches[1] . "\n\n";


/**
 * Short Link Enable:  <a href="https://google.com/">link</a>
 * Shrot Link Disable: https://google.com/
 */
$msg .= (ShortLink ? '<a href="' : '') .  enHTML($url) . (ShortLink ? '">link</a>' : '');

sendMsg($msg);


/**
 * Send message to channel via Telegram bot API
 * @param string $message
 */
function sendMsg(string $messsage) {
	$url = 'https://api.telegram.org/bot' . Token . '/sendMessage';
	$params = Array(
		'chat_id' => Channel,
		'text' => $messsage,
		'parse_mode' => 'HTML',
	);
	$params = json_encode($params);
	file_get_contents($url, False, stream_context_create(Array(
		'http' => Array(
			'method'  => 'POST',
			'header'  => Array(
				'Content-Type: application/json; charset=utf-8',
			),
			'content' => $params,
		),
	)));
}

/**
 * Replace &, ", <, > to HTML entity
 * Offical document: https://core.telegram.org/bots/api#html-style
 * @param string $str
 * @return string $str Encoded string
 */
function enHTML(string $str = ''): string {
	$search =  Array('&', '"', '<', '>');
	$replace = Array('&amp;', '&quot;', '&lt;', '&gt');
	$str = str_replace($search, $replace, $str);
	return $str;
}
