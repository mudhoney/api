<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Database_MovieDatabase Class definition
 * Provides methods for querying and storing movies generated by
 * Helioviewer.org
 *
 * @category Database
 * @package  Helioviewer
 * @author   Jeff Stys <jeff.stys@nasa.gov>
 * @author   Keith Hughitt <keith.hughitt@nasa.gov>
 * @author   Serge Zahniy <serge.zahniy@nasa.gov>
 * @license  http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License 1.1
 * @link     https://github.com/Helioviewer-Project
 */

class Database_MovieDatabase {

    private $_dbConnection;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        $this->_dbConnection = false;
    }

    /**
     * Create a connection to the database if one has not already been made.
     *
     * @return void
     */
    private function _dbConnect() {
        if ( $this->_dbConnection === false ) {
            include_once HV_ROOT_DIR.'/../src/Database/DbConnection.php';
            $this->_dbConnection = new Database_DbConnection();
        }
    }

    /**
     * Insert a new movie entry into the `movies` table and returns its
     * identifier.
     *
     * @return int  Identifier in the `movies` table or boolean false
     */
    public function insertMovie($startTime, $endTime, $reqObservationDate, $imageScale, $roi,
        $maxFrames, $watermark, $layerString, $layerBitMask, $eventString,
        $eventsLabels, $movieIcons, $followViewport, $scale, $scaleType, $scaleX, $scaleY, $numLayers,
        $queueNum, $frameRate, $movieLength, $size, $switchSources, $celestialBodies) {

        $this->_dbConnect();

        $startTime = isoDateToMySQL($startTime);
        $endTime   = isoDateToMySQL($endTime);
        if($reqObservationDate != false){
	        $reqObservationDate   = '"'.$this->_dbConnection->link->real_escape_string(isoDateToMySQL($reqObservationDate)).'"';
        }else{
	        $reqObservationDate   = "NULL";
        }

        $sql = sprintf(
                   'INSERT INTO movies '
                 . 'SET '
                 .     'id '                . ' = NULL, '
                 .     'timestamp '         . ' = CURRENT_TIMESTAMP, '
                 .     'reqStartDate '      . ' ="%s", '
                 .     'reqEndDate '        . ' ="%s", '
                 .     'reqObservationDate '. ' ='.$reqObservationDate.', '
                 .     'imageScale '        . ' = %f, '
                 .     'regionOfInterest '  . ' = ST_PolygonFromText("%s"), '
                 .     'maxFrames '         . ' = %d, '
                 .     'watermark '         . ' = %b, '
                 .     'dataSourceString '  . ' ="%s", '
                 .     'dataSourceBitMask ' . ' = %d, '
                 .     'eventSourceString ' . ' ="%s", '
                 .     'eventsLabels '      . ' = %b, '
                 .     'movieIcons '        . ' = %b, '
                 .     'followViewport '    . ' = %b, '
                 .     'scale '             . ' = %b, '
                 .     'scaleType '         . ' ="%s",'
                 .     'scaleX '            . ' = %f, '
                 .     'scaleY '            . ' = %f, '
                 .     'numLayers '         . ' = %d, '
                 .     'queueNum '          . ' = %d, '
                 .     'frameRate '         . ' = %f, '
                 .     'movieLength '       . ' = %f, '
                 .     'startDate '         . ' = NULL, '
                 .     'endDate '           . ' = NULL, '
                 .     'numFrames '         . ' = NULL, '
                 .     'width '             . ' = NULL, '
                 .     'height '            . ' = NULL, '
                 .     'buildTimeStart '    . ' = NULL, '
                 .     'buildTimeEnd '      . ' = NULL, '
                 .     'size '              . ' = %d, '
                 .     'switchSources '     . ' = %b, '
                 .     'celestialBodiesLabels ' . ' = "%s", '
                 .     'celestialBodiesTrajectories ' . ' = "%s";',
                 $this->_dbConnection->link->real_escape_string($startTime),
                 $this->_dbConnection->link->real_escape_string($endTime),
                 (float)$imageScale,
                 $this->_dbConnection->link->real_escape_string($roi ),
                 (int)$maxFrames,
                 (bool)$watermark,
                 $this->_dbConnection->link->real_escape_string($layerString),
                 bindec($this->_dbConnection->link->real_escape_string((binary)$layerBitMask ) ),
                 $this->_dbConnection->link->real_escape_string($eventString),
                 (bool)$eventsLabels,
                 (bool)$movieIcons,
                 (bool)$followViewport,
                 (bool)$scale,
                 $this->_dbConnection->link->real_escape_string($scaleType ),
                 (float)$scaleX,
                 (float)$scaleY,
                 (int)$numLayers,
                 (int)$queueNum,
                 (float)$frameRate,
                 (float)$movieLength,
                 (int)$size,
                 (bool)$switchSources,
                 $this->_dbConnection->link->real_escape_string($celestialBodies['labels']),
                 $this->_dbConnection->link->real_escape_string($celestialBodies['trajectories'])
               );

        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        return $this->_dbConnection->getInsertId();
    }

    /**
     * Insert an entry into the `movieFormats` table.
     *
     * @param  int  $movieId  Identifier in the `movies` table
     * @param  int  $format   Movie format file extension
     *
     * @return int Identifier in the `movieFormats` table or boolean false
     */
    public function insertMovieFormat($movieId, $format) {
        $this->_dbConnect();

        $sql = sprintf(
                   'INSERT INTO movieFormats '
                 . 'SET '
                 .     'id '       . ' = NULL, '
                 .     'movieId '  . ' = %d, '
                 .     'format '   . ' ="%s", '
                 .     'status '   . ' = 0, '  // 0 = 'queued'
                 .     'procTime ' . ' = NULL;',
                 (int)$movieId,
                 $this->_dbConnection->link->real_escape_string($format)
               );
        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        return $this->_dbConnection->getInsertId();
    }

    /**
     * Insert an entry into the `youtube` table to keep track of user-generated
     * movies shared to YouTube.
     *
     * @return int Identifier in `youtube` table or boolean false
     */
    public function insertYouTubeMovie($movieId, $title, $desc, $keywords,
        $share) {

        $this->_dbConnect();

        $sql = sprintf(
                   'INSERT INTO youtube '
                 . 'SET '
                 .     'id '          . ' = NULL, '
                 .     'movieId '     . ' = %d, '
                 .     'youtubeId '   . ' = NULL, '
                 .     'timestamp '   . ' = CURRENT_TIMESTAMP, '
                 .     'title '       . ' ="%s", '
                 .     'description ' . ' ="%s", '
                 .     'keywords '    . ' ="%s", '
                 .     'shared '      . ' = %b;',
                 (int)$movieId,
                 $this->_dbConnection->link->real_escape_string($title),
                 $this->_dbConnection->link->real_escape_string($desc),
                 $this->_dbConnection->link->real_escape_string($keywords),
                 (bool)$share
               );
        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        return $this->_dbConnection->getInsertId();
    }

    /**
     * Update an entry in the `youtube` entry to add the YouTube identifier
     * string after the upload completed successfully.
     *
     * @param  int  $movieId    Identifier in the `movies` table
     * @param  str  $youtubeId  YouTube identifier string
     *
     * @return boolean true or false
     */
    public function updateYouTubeMovie($movieId, $youtubeId) {
        $this->_dbConnect();

        $sql = sprintf(
                  'UPDATE youtube '
                . 'SET '
                .     'youtubeId = "%s" '
                . 'WHERE '
                .     'movieId = %d '
                . 'LIMIT 1;',
                $this->_dbConnection->link->real_escape_string($youtubeId),
                (int)$movieId
               );
        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Fetch statistics from the `movies` table for the N most recently
     * generated movies.
     *
     * @param  int  $n  Number of movies
     *
     * @return array  Array of movie statistics or boolean false
     */
    public function getMovieStatistics($n=100) {
        $this->_dbConnect();

        $sql = sprintf(
                   'SELECT '
                 .     'numFrames, '
                 .     'width * height AS numPixels, '
                 .     'queueNum, '
                 .     'TIMESTAMPDIFF(SECOND, buildTimeStart, buildTimeEnd) '
                 .         'AS time '
                 .  'FROM movies '
                 .  'WHERE '
                 .     'TIMESTAMPDIFF(SECOND, buildTimeStart, buildTimeEnd) '
                 .         '> 0 '
                 .  'ORDER BY id DESC '
                 .  'LIMIT %d;',
                 (int)$n
               );
        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        // Fetch result and store as column arrays instead of rows
        $stats = array(
            "numFrames" => array(),
            "numPixels" => array(),
            "queueNum"  => array(),
            "time"      => array()
        );
        while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
            array_push($stats['numFrames'], $row['numFrames']);
            array_push($stats['numPixels'], $row['numPixels']);
            array_push($stats['queueNum'],  $row['queueNum']);
            array_push($stats['time'],      $row['time']);
        }

        return $stats;
    }

    /**
     * Get a list of movies recently shared on YouTube from a cache file
     * or from a live database query.
     *
     * @param int  $num    Number of movies to return
     * @param str  $since  ISO date
     * @param bool $force  Force reading from database instead of cache
     *
     * @return arr Array containing movieId, youtubeId, timestamp for each
     *             of the matched movies or boolean false.
     */
    public function getSharedVideos($num, $skip, $since, $force=false) {

        include_once HV_ROOT_DIR.'/../src/Helper/DateTimeConversions.php';

        $cached = false;

        if ( HV_DISABLE_CACHE !== true || $force===false ) {
            include_once HV_ROOT_DIR.'/../src/Helper/Serialize.php';

            $cachedir = 'api/MovieDatabse/getSharedvideos';
            $filename = urlencode($since.'_'.$num.'.cache');
            $filepath = $cachedir.'/'.$filename;

            $cache = new Helper_Serialize($cachedir, $filename, 90);

            // Read cache (and invalidate if older than
            // Helper_Serialize::_maxAgeSec)
            $videos = $cache->readCache(true);
            if ( $videos !== false ) {
                $cached = true;
            }
        }

        // Load data directly from the database
        if ( $cached !== true || $force===true ) {
            $this->_dbConnect();

            $date = isoDateToMySQL($since);

            $sql = sprintf(
                   'SELECT youtube.movieId, youtube.youtubeId, youtube.timestamp, youtube.title, youtube.description, '
                 . 'youtube.keywords, youtube.shared, movies.imageScale, movies.dataSourceString, movies.eventSourceString, '
                 . 'movies.movieLength, movies.width, movies.height, movies.startDate, movies.endDate '
                 . 'FROM youtube '
                 . 'LEFT JOIN movies '
                 . 'ON movies.id = youtube.movieId '
                 . 'WHERE '
                 .     'youtube.shared>0 AND '
                 .     'youtube.youtubeId IS NOT NULL AND '
                 .     'youtube.timestamp > "%s" '
                 . 'ORDER BY youtube.timestamp DESC '
                 . 'LIMIT %d,%d;',
                 mysqli_real_escape_string($this->_dbConnection->link, $date),
                 (int)$skip,
                 (int)$num
               );
            try {
                $result = $this->_dbConnection->query($sql);
            }
            catch (Exception $e) {
                return false;
            }

            $videos = array();
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                array_push($videos, $row);
            }

            if ( HV_DISABLE_CACHE !== true ) {
                if ( $cache->writeCache($videos) ) {
                    $cached = true;
                }
            }
        }

        return $videos;
    }

    /**
     * Get a list of movies recently shared on YouTube from a cache file
     * or from a live database query.
     *
     * @param int  $num    Number of movies to return
     * @param str  $since  ISO date
     * @param bool $force  Force reading from database instead of cache
     *
     * @return arr Array containing movieId, youtubeId, timestamp for each
     *             of the matched movies or boolean false.
     */
    public function getSharedVideosByTime($num, $skip, $date) {
        include_once HV_ROOT_DIR.'/../src/Helper/DateTimeConversions.php';
	    include_once HV_ROOT_DIR.'/../src/Net/Proxy.php';

        // Load data directly from the database
        $this->_dbConnect();

        $date = isoDateToMySQL($date);

        $sql = sprintf(
                   'SELECT youtube.id, youtube.movieId, youtube.youtubeId, youtube.timestamp, youtube.title, youtube.description, '
                 . 'youtube.keywords, youtube.thumbnail, youtube.shared, youtube.checked, movies.imageScale, movies.dataSourceString, movies.eventSourceString, '
                 . 'movies.movieLength, movies.width, movies.height, movies.startDate, movies.endDate, ST_AsText(regionOfInterest) as roi '
                 . 'FROM youtube '
                 . 'LEFT JOIN movies '
                 . 'ON movies.id = youtube.movieId '
                 . 'WHERE '
                 .     'youtube.shared>0 AND '
                 .     'youtube.youtubeId IS NOT NULL AND '
                 .     '("%s" BETWEEN movies.startDate AND movies.endDate) '
                 . 'ORDER BY movies.startDate DESC ',
                 mysqli_real_escape_string($this->_dbConnection->link, $date)
               );
        if((int)$num > 0){
	        $sql .= sprintf(' LIMIT %d,%d;',
                 (int)$skip,
                 (int)$num
               );
        }
        
        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        $videos = array();
        $timestamp = time();
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			if(strtotime($row['checked']) < (time() - 30*24*60*60) || empty($row['thumbnail'])){
				//Check if Video is still exist/shared on YouTube
				$videoID = $row['youtubeId'];
				$theURL = "http://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=$videoID&format=json";
		        $proxy = new Net_Proxy($theURL);
			    $response = $proxy->query(array(), true);
		
			    if($response == 'Not Found' || $response == 'Unauthorized'){
			        $this->_dbConnection->query('UPDATE youtube SET shared = 0 WHERE id = '.$row['id'].'');
			    } else {
			        $data = json_decode($response, true);
			        $row['thumbnail'] = $data['thumbnail_url'];
			        $this->_dbConnection->query('UPDATE youtube SET thumbnail = "'.$data['thumbnail_url'].'", checked=NOW() WHERE id = '.$row['id'].'');
			        array_push($videos, $row);
			    }
		    }else{
			    array_push($videos, $row);
		    }
        }

        return $videos;
    }

    /**
     * Get a movie's information from the database.
     *
     * @param str  $movieId  Movie identifier
     *
     * @return arr Associative array
     */
    public function getMovieMetadata($movieId) {
        $this->_dbConnect();

        $sql = sprintf('SELECT *, ST_AsText(regionOfInterest) as roi '
             . 'FROM movies WHERE movies.id=%d LIMIT 1;',
             (int)$movieId
        );
        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        return $result->fetch_array(MYSQLI_ASSOC);
    }

    /**
     * Get information about a movie's format(s) from the database.
     *
     * @param str  $movieId  Movie identifier
     *
     * @return arr Multi-dimensional associative array
     */
    public function getMovieFormats($movieId) {
        $this->_dbConnect();

        $sql = sprintf('SELECT * '
             . 'FROM movieFormats WHERE movieId=%d;',
             (int)$movieId
        );
        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        $movieFormats = array();

        while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
            array_push($movieFormats, $row);
        }

        return $movieFormats;
    }

    /**
     * Delete a movie's format entrie(s) from the database.
     *
     * @param str  $movieId  Movie identifier
     *
     * @return bool Boolean True or False (for DELETE queries)
     */
    public function deleteMovieFormats($movieId) {
        $this->_dbConnect();

        $sql = sprintf('DELETE FROM movieFormats WHERE movieId=%d;',
             (int)$movieId
        );

        try {
            $result = $this->_dbConnection->query($sql);
        }
        catch (Exception $e) {
            return false;
        }

        return $result;
    }
}
