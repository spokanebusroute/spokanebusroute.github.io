<?php


ini_set('display_errors', true);
error_reporting(E_ALL ^ E_NOTICE);

/**
 *
 * Allow for jsonp requests  
 *
 */
function json_echo($data, $encode=true, $callback='callback') {
    if ($encode) { 
        // JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        $json = json_encode($data);
    } else {
        // Cached data, previously encoded
        $json = $data;
    }
    //header('Content-Type: application/json; charset=utf-8');
    header('Content-Type: text/javascript; charset=utf-8'); 
    echo isset($_REQUEST[$callback])
        ? "{$_REQUEST[$callback]}($json)"
        : $json;    
}

$api = new SG_STA_GTFS();

class SG_STA_GTFS {

	var $db = './db/sta-gtfs.sqlite';
	var $pdo;
	var $api;

	function __construct() {
		$this->pdo = new PDO('sqlite:'.$this->db); 
		// Some PDO errors (e.g. execute) will fail silently w/o this attribute set
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE 
                            ,PDO::ERRMODE_EXCEPTION);
		$this->api = new stdClass();
		
		$this->init();
	}

	public function output() {
		//header('Content-Type: application/json');
		//echo json_encode($this->api);
		json_echo($this->api);
	}

	public function query($sql, $params) {
		$db = $this->pdo->prepare($sql);
		$db->execute($params);

		return $db->fetchAll(PDO::FETCH_OBJ);
	}

	public function init() {
		$rest = explode('/', $_REQUEST['endpoint']);
		switch ($rest[0]) {
			case 'params':
				$this->api = $this->getParams();
				break;
			case 'timetable':
				$api = new stdClass();
				$api->route = $this->getRoute($rest[1]);
				$api->trips = $this->getTrips($api->route->route_id, $rest[2], $rest[3]);
				foreach ( $api->trips as $k => $trip ) {
					$api->trips[$k]->times = $this->getTimes($trip->trip_id);
				}
				foreach ( $api->trips as $k => $trip ) {
					$api->trips[$k]->depart = $trip->times[0];
					$api->trips[$k]->arrive = $trip->times[count($trip->times)-1];
					$api->trips[$k]->times[count($trip->times)-1]->last_stop = true;
				}
				$this->api = $api;
				break;
			case 'stoptable':
				$api = new stdClass();
				$api->route = $this->getRoute($rest[1]);
				$api->trips = $this->getTrips($api->route->route_id, $rest[2], $rest[3]);
				$trips = array();
				foreach ( $api->trips as $k => $trip ) {
					$trips[] = $trip->trip_id;
				}
				$api->stops = $this->getStops($trips);
				$this->api = $api->stops;
				break;
			case 'agency':
				$this->api = $this->getAgency();
				break;
			case 'calendar':
				$sid = isset($rest[1]) ? $rest[1] : null;
				$this->api = $this->getCalendar($sid);
				break;
			case 'route':
				$this->api = $this->getRoute($rest[1]);
				break;
			case 'routes':
				$this->api = $this->getRoutes();
				break;
			case 'trips':
				$this->api = $this->getTrips();
				break;
			case 'trip':
				$this->api = $this->getTrip($rest[1]);
				break;
			case 'times':
				$this->api = $this->getTimes($rest[1]);
				break;
			case 'stop':
				//$this->api = $this->getStopByTrip($rest[1], $rest[2]);
				
				$api = new stdClass();
				$api->route = $this->getRoute($rest[1]);
				$api->trips = $this->getTrips($api->route->route_id, $rest[2], $rest[3]);
				$trips = array();
				foreach ( $api->trips as $k => $trip ) {
					$trips[] = $trip->trip_id;
				}
				$api->times = $this->getStop($rest[4], $trips);
				$api->stop = $this->getStopById($rest[4]);
				unset($api->trips);
				$this->api = $api;
				break;
			default:
				break;
		}
		
		if ( $this->api && !empty($this->api) ) {
			$this->output();
		}

	}

	protected function getParams() {
		$params = new stdClass();

		$params->direction->outbound = 0;
		$params->direction->inbound = 1;

		$params->service->weekday = 1;
		$params->service->saturday = 2;
		$params->service->sunday = 3;
		
		$params->today->weekday = false;
		$params->today->saturday = false;
		$params->today->sunday = false;
		$now = new DateTime;
		switch($now->format('N')) {
			case 6:
				$params->today->saturday = true;
				break;
			case 7:
				$params->today->sunday = true;
				break;
			default:
				$params->today->weekday = true;
				break;
		}


		return $params;
	}

	protected function getAgency() {
		$params = array();
		
		$sql = "SELECT
						*
						FROM agency
					";
		
		return $this->query($sql, $params);
	}

	protected function getCalendar($sid=null) {
		$params = array();
		
		$sql = "SELECT
						*
						FROM calendar
					";

		if ( !is_null($sid) ) {
			$params[':sid'] = $sid;
			$sql .= "
							WHERE
							service_id = :sid
							";
		}
		
		return $this->query($sql, $params);
	}

	protected function getRoutes() {
		$params = array();
		
		$sql = "SELECT
						*
						FROM routes
						ORDER BY route_short_name*100 -- fake a natsort
					";
		
		return $this->query($sql, $params);
	}

	protected function getRoute($route) {
		$params = array(':route'=>$route);
		
		$sql = "SELECT
						*
						FROM routes
						WHERE route_short_name = :route
					";

		$q = $this->query($sql, $params);
		return $q[0];
	}

	protected function getTrips ( $rid, $did=1, $sid=1 ) {
		$params = array(':rid'=>$rid, ':did'=>$did, ':sid'=>$sid);
		
		$sql = "SELECT
						*
						FROM trips
						WHERE route_id = :rid
						AND direction_id = :did
						AND service_id = :sid
					";
		
		return $this->query($sql, $params);
	}

	protected function getTrip($trip) {
		$params = array(':trip'=>$trip);

		$sql = "SELECT
						*
						FROM trips
						WHERE trip_id = :trip
					";

		$q = $this->query($sql, $params);
		return $q[0];
	}

	protected function getTimes($trip) {
		$params = array(':trip'=>$trip);

		$sql = "SELECT
						*
						FROM stop_times
						JOIN stops
							ON stop_times.stop_id = stops.stop_id
						WHERE trip_id = :trip
						ORDER BY stop_sequence*100 -- fake a natsort
					";

		$q = $this->query($sql, $params);
		foreach ( $q as $k => $time ) {
			if ( substr($time->departure_time, -3) == ':00' ) {
				$q[$k]->major_stop = true;
			}
			$arrival = new DateTime($time->arrival_time);
			$q[$k]->arrival_time_formatted = $arrival->format('g:i a');
			$departure = new DateTime($time->departure_time);
			$q[$k]->departure_time_formatted = $departure->format('g:i a');
		}
		return $q;
	}

	protected function getStops($trips) {
		/*
		$params = array();
		$sql = "SELECT DISTINCT stops.stop_id, stops.stop_name
					  FROM trips
					  INNER JOIN stop_times ON stop_times.trip_id = trips.trip_id
					  INNER JOIN stops ON stops.stop_id = stop_times.stop_id
					  WHERE route_id = '45'
					";
		$q = $this->query($sql, $params);
		return $q;
		*/

		// http://www.php.net/manual/en/pdostatement.execute.php
		// Create a string for the parameter placeholders filled to the number of params
		$tids = implode(',', array_fill(0, count($trips), '?'));
		$sql = "SELECT
						DISTINCT stop_id
						FROM stop_times
						WHERE trip_id IN ($tids) 
						ORDER BY stop_sequence*100 -- fake a natsort
					";
		$q = $this->query($sql, $trips);
		$stops = array();
		foreach ( $q as $k => $stop ) {
			$stops[$k] = $stop->stop_id;
		}
		
		$sids = implode(',', array_fill(0, count($stops), '?'));
		$sql = "SELECT
						*
						FROM stops
						WHERE stop_id IN ($sids) 
					";
		$q = $this->query($sql, $stops);
		
		return $q;
	}

	protected function getStop($sid, $trips) {

		// http://www.php.net/manual/en/pdostatement.execute.php
		// Create a string for the parameter placeholders filled to the number of params
		$tids = implode(',', array_fill(0, count($trips), '?'));
		$sql = "SELECT
						*
						FROM stop_times
						WHERE trip_id IN ($tids) 
						--ORDER BY stop_sequence*100 -- fake a natsort
						ORDER BY departure_time*100
					";
		$q = $this->query($sql, $trips);
		
		$stop = array();
		foreach ( $q as $k => $time ) {
			if ( $time->stop_id == $sid ) {
				$arrival = new DateTime($time->arrival_time);
				$time->arrival_time_formatted = $arrival->format('g:i a');
				$departure = new DateTime($time->departure_time);
				$time->departure_time_formatted = $departure->format('g:i a');
				$stop[] = $time;
			}
		}

		return $stop;
	}

	protected function getStopById($stop) {
		$params = array(':stop'=>$stop);

		$sql = "SELECT
						*
						FROM stops
						WHERE stop_id = :stop
					";

		$q = $this->query($sql, $params);

		return $q[0];
	}

}

?>
