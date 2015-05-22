<?php 

namespace Prerender;

use Closure;
use App;
use URL;
use Request;
use Illuminate\Routing\ResourceRegistrar;
use Response;

class PrerenderServiceProvider {

	/**
	 * The Guard implementation.
	 *
	 * @var Guard
	 */
	private $backendURL;
	private $prerenderToken;
	/**
	 * Create a new filter instance.
	 *
	 * @param  Guard  $auth
	 * @return void
	 */
	public function __construct(){
		$this->backendURL = "http://localhost:3000/";
	}

	public function handle($request, Closure $next)	{
		if ($this->shouldPreRender()){
        	$this->preRender();
      	} else {
        	return $next($request);
      	}
	}

	public function shouldPreRender(){
    	// return false if not a bot
    	if (!$this->isBot()){
      		return false;
    	}
    	// don't preRender if ignored extension
    	if ($this->isIgnoredExtension()){
      		return false;
    	}
    	return true;
  	}
	
	public function isBot(){
		// Google and other engines using this
		if (isset($_GET['_escaped_fragment_'])){
		  	return true;
		}
		$agent = Request::header('user-agent', 0);
		//$agent = 'Googlebot';
		// regex with our bot list
		$bots = "!(Googlebot|bingbot|Googlebot-Mobile|Yahoo|YahooSeeker|FacebookExternalHit|Twitterbot|TweetmemeBot|BingPreview|developers.google.com/\+/web/snippet/)!i";
		// if anything in our search string is in the user agent
		if (preg_match($bots, $agent)){
			return true;
		}
		// not a bot
		return false;
	}

  	public function isIgnoredExtension(){
  		$url = Request::fullUrl();
  		$ext = "." . pathinfo($url, PATHINFO_EXTENSION);
    	$extensions = "!(\.js|\.css|\.xml|\.less|\.png|\.jpg|\.jpeg|\.gif|\.pdf|\.doc|\.txt|\.ico|\.rss|\.zip|\.mp3|\.rar|\.exe|\.wmv|\.doc|\.avi|\.ppt|\.mpg|\.mpeg|\.tif|\.wav|\.mov|\.psd|\.ai|\.xls|\.mp4|\.m4a|\.swf|\.dat|\.dmg|\.iso|\.flv|\.m4v|\.torrent)!i";
    	if (preg_match($extensions, $ext)){
      		return true;
    	} else {
      		return false;
    	}
  	}

  	public function preRender(){
  		$ch = curl_init();
    	$url = $this->backendURL . Request::fullUrl();
    	$headers = [
            'User-Agent:' . Request::header('user-agent', 0),
        ];
        if ($this->prerenderToken) {
        	array_push($headers, 'X-Prerender-Token:'.$this->prerenderToken);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $content = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $status;
    	return new Response($content, $status);
  	}
}