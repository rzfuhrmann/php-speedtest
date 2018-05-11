<?php
	namespace RZFuhrmann;
	if (!class_exists('RZFuhrmann\Speedtest'))  {
		class Speedtest {
			public function __construct (){

			}

			public function test(){
				$result = array();

				// getting active servers
				$this->logtxt('Getting active speedtest servers...');
				$servers = $this->getServers();
				if (!$servers){
					$this->logtxt('Error fetching server list!', 'error');
				} else {
					// getting configuration
					$this->logtxt('Getting speedtest configuration...');
					$result['config'] = $this->getConfig();

					// search nearest server
					$this->logtxt('Calculating nearest Server based on position...');
					$result['server'] = $this->getNearestServer($servers, array('lat' => $result['config']['client']['lat'], 'lon' => $result['config']['client']['lon']));

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
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); 
				curl_setopt($ch, CURLOPT_INTERFACE, "192.168.1.45");
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');
				$raw = curl_exec($ch); 
				if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
					return false;
				}
				$info = curl_getinfo($ch);
				return $info;
			}

			/**
			 * Makes a simple GET request to a given URL. 
			 */
			private function getHTML($url){
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); 
				curl_setopt($ch, CURLOPT_INTERFACE, "192.168.1.45");
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');
				$raw = curl_exec($ch); 

				if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
					return false;
				}
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
						'lat' => $client->getAttribute('lat'),
						'lon' => $client->getAttribute('lon'),
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
						'lat' => $server->getAttribute("lat"),
						'lon' => $server->getAttribute("lon"),
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