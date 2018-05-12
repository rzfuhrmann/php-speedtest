<?php
	namespace RZFuhrmann;
	if (!class_exists('RZFuhrmann\Speedtest'))  {
		class Speedtest {
			private $source_address = null;
			
			public function __construct ($opts = array()){
				$this->applyOpts($opts);
			}

			public function setOpt($opt, $value){
				$this->applyOpts(array($opt => $value));
			}

			private function applyOpts($opts = array()){
				if (isset($opts['source_address'])){
					$this->source_address = $opts['source_address'];
				}
			}

			public function test(){
				$result = array();

				// getting configuration
				$this->logtxt('Getting speedtest configuration...');
				$result['config'] = $this->getConfig();

				if (!$result['config']){
					$this->logtxt('Error fetching speedtest configuration!', 'error');
				} else {
					// getting active servers
					$this->logtxt('Getting active speedtest servers...');
					$servers = $this->getServers();
					if (!$servers){
						$this->logtxt('Error fetching server list!', 'error');
					} else {
						// search nearest server
						$this->logtxt('Calculating nearest server based on its position...');
						$result['server'] = $this->getNearestServer($servers, array('lat' => $result['config']['client']['lat'], 'lon' => $result['config']['client']['lon']));

						if (!$result['server']){
							$this->logtxt('Error calculating nearest server!', 'error');
						} else {
							// latency
							$latencies = array();
							for ($i = 0; $i < 3; $i++){
								$url = $result['server']['url'].'/latency.txt?x='.(microtime(true)*1000).'.'.$i;
								$info = $this->getHTTPInfo($url);
								if ($info && $info["connect_time"]){
									$latencies[] = $info["connect_time"];
								} else {
									$i -= 1;
								}
							}
							$result['latency'] = round((array_sum($latencies)/count($latencies))*1000, 2);
							$this->logtxt('Latency: '.$result['latency'].' ms');
						}
					}
				}
				return $result;
			}

			private function LatLon2Distance ($pos1, $pos2){
				$R = 6371e3;
				$phi1 = deg2rad($pos1["lat"]);
				$phi2 = deg2rad($pos2["lat"]);
				$dphi = deg2rad($pos2["lat"]-$pos1["lat"]);
				$dlambda = deg2rad($pos2["lon"]-$pos1["lon"]);
		
				$a = sin($dphi/2) * sin($dphi/2) +
					cos($phi1) * cos($phi2) *
					sin($dlambda/2) * sin($dlambda/2);
				$c = 2 * atan2(sqrt($a), sqrt(1-$a));
				
				return $R * $c;
			}

			private function getNearestServer($serverlist, $latlong){
				$min_dist = null; $min_server = null;
				foreach ($serverlist as $server){
					$dist = $this->LatLon2Distance($server, $latlong);
					if (!$min_dist || $dist < $min_dist){
						$min_dist = $dist; 
						$min_server = $server;
					}
				}
				return $min_server;
			}

			/**
			 * Get HTTP info
			 */
			private function getHTTPInfo($url){
				return $this->getHTML($url, true);
			}

			/**
			 * Makes a simple GET request to a given URL. 
			 */
			private function getHTML($url, $return_info = false){
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); 
				if ($this->source_address) curl_setopt($ch, CURLOPT_INTERFACE, $this->source_address);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');
				$raw = curl_exec($ch); 

				if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
					return false;
				}
				$info = curl_getinfo($ch);
				curl_close($ch);
				if ($return_info) return $info;
				
				return $raw;
			}

			/**
			 * Returns an DOMDocument of an downloaded XML
			 */
			private function getXML($url){
				$raw = $this->getHTML($url);
				$xml = new \DOMDocument();
				$xml->loadXML($raw);

				return $xml;
			}

			/**
			 * Get Speedtest configuration from http://www.speedtest.net/speedtest-config.php
			 */
			private function getConfig(){
				$config = array(
					'client' => null
				);
				$configxml = $this->getXML('http://www.speedtest.net/speedtest-config.php');

				if ($client = $configxml->getElementsByTagName('client')[0]){
					$config['client'] = array(
						'ip' => $client->getAttribute('ip'),
						'lat' => floatval($client->getAttribute('lat')),
						'lon' => floatval($client->getAttribute('lon')),
						'isp' => $client->getAttribute('isp'),
						'isprating' => $client->getAttribute('isprating'),
						'country' => $client->getAttribute('country'),
						// 'rating' => $client->getAttribute('rating'),
						// 'ispdlavg' => $client->getAttribute('ispdlavg'),
						// 'ispulavg' => $client->getAttribute('ispulavg'),
					);
				}

				return $config; 
			}
			
			/**
			 * Get all servers available at http://www.speedtest.net/speedtest-servers-static.php
			 */
			private function getServers(){
				$xml = $this->getXML("http://www.speedtest.net/speedtest-servers-static.php");
				$servers = $xml->getElementsByTagName("server");
				
				$serverlist = array();

				foreach ($servers as $server){
					$this_server = array(
						'url' => $server->getAttribute("url"),
						'lat' => floatval($server->getAttribute("lat")),
						'lon' => floatval($server->getAttribute("lon")),
						'name' => $server->getAttribute("name"),
						'country' => $server->getAttribute("country"),
						'cc' => $server->getAttribute("cc"),
						'sponsor' => $server->getAttribute("sponsor"),
						'id' => $server->getAttribute("id"),
						'host' => $server->getAttribute("host"),
					);
					$serverlist[] = $this_server;
				}

				return $serverlist;
			}

			private function logtxt($txt, $lvl = "info"){
				echo '['.date("H:i:s").'] '.$txt."\n";
			}
		}
	}
?>