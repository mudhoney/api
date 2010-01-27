<?php

require_once("interface.Module.php");

class JHelioviewer implements Module
{
    private $params;

    public function __construct($params)
    {
        require_once("Helper.php");
        $this->params = $params;


        $this->execute();

    }

    public function execute()
    {
        if($this->validate())
        {
            $this->{$this->params['action']}();
        }
    }

    public function validate()
    {
        switch($this->params['action'])
        {
            case "getJP2Image":
                $bools = array("getURL", "getJPIP");
                $this->params = Helper::fixBools($bools, $this->params);
                break;
            case "buildJP2ImageSeries":
                $bools = array("getURL", "getJPIP", "links", "debug");
                $this->params = Helper::fixBools($bools, $this->params);

                if ($this->params['links'] && ($this->params['format'] != "JPX"))
                die('<b>Error</b>: Format must be set to "JPX" in order to create a linked image series.');
                break;
            case "getJPX":
                break;
            case "getMJ2":
                break;
            case "getJP2ImageSeries":
                break;
            default:
                throw new Exception("Invalid action specified. See the <a href='http://www.helioviewer.org/api/'>API Documentation</a> for a list of valid actions.");
        }
        return true;
    }

    public static function printDoc()
    {

    }

    /**
     * @description Converts a regular HTTP URL to a JPIP URL
     */
    private function getJPIPURL($url) {
        $webRootRegex = "/" . preg_replace("/\//", "\/", HV_JP2_DIR) . "/";
        $jpip = preg_replace($webRootRegex, HV_JPIP_ROOT_URL, $url);
        return $jpip;
    }

    /**
     * @return int Returns "1" if the action was completed successfully.
     */
    public function getJP2Image ()
    {
        require_once('lib/ImgIndex.php');
        require_once("lib/DbConnection.php");
        $imgIndex = new ImgIndex(new DbConnection());

        $date = $this->params['date'];

        // Search by source id
        if (!isset($this->params['source']))
        $this->params['source'] = $imgIndex->getSourceId($this->params['observatory'], $this->params['instrument'], $this->params['detector'], $this->params['measurement']);

        $filepath = $imgIndex->getJP2FilePath($date, $this->params['source']);

        $uri = HV_JP2_DIR . $filepath;

        // http url (full path)
        if ($this->params['getURL']) {
            $webRootRegex = "/" . preg_replace("/\//", "\/", HV_JP2_DIR) . "/";
            $url = preg_replace($webRootRegex, HV_JP2_ROOT_URL, $uri);
            echo $url;
        }

        // jpip url
        else if ($this->params['getJPIP']) {
            echo $this->getJPIPURL($uri);
        }
         
        // jp2 image
        else {
            $fp = fopen($uri, 'r');
            $stat = stat($uri);

            $exploded = explode("/", $filepath);
            $filename = end($exploded);

            header("Content-Length: " . $stat['size']);
            header("Content-Type: "   . image_type_to_mime_type(IMAGETYPE_JP2));
            header("Content-Disposition: attachment; filename=\"$filename\"");

            $contents = fread($fp, $stat['size']);

            echo $contents;
            fclose($fp);
        }

        return 1;
    }

    public function getJPX ()
    {
        $this->params['format'] = 'JPX';
        $this->params['action'] = 'buildJP2ImageSeries';
        $this->execute();
    }

    public function getMJ2 ()
    {
        $this->params['format'] = 'MJ2';
        $this->params['action'] = 'buildJP2ImageSeries';
        $this->execute();
    }
    
    public function getJP2ImageSeries () {
        $this->params['action'] = 'buildJP2ImageSeries';
        $this->execute();
    }

    /**
     * @return int Returns "1" if the action was completed successfully.
     * @param string The filename to use
     *  Converting timestamp to a PHP DateTime:
     *     $dt = new DateTime("@$startTime");
     *     echo $dt->format(DATE_ISO8601);
     *     date_add($dt, new DateInterval("T" . $cadence . "S"));
     *  (See http://us2.php.net/manual/en/function.date-create.php)
     */

    /**
     *
     * Constructs a JPX/MJ2 image series
     */
    private function buildJP2ImageSeries () {
        require_once('lib/ImgIndex.php');
        require_once('lib/DbConnection.php');

        $startTime   = toUnixTimestamp($this->params['startTime']);
        $endTime     = toUnixTimestamp($this->params['endTime']);
        $cadence     = $this->params['cadence'];
        $jpip        = $this->params['getJPIP'];
        $format      = $this->params['format'];
        $links       = $this->params['links'];
        $debug       = $this->params['debug'];
        $observatory = $this->params['observatory'];
        $instrument  = $this->params['instrument'];
        $detector    = $this->params['detector'];
        $measurement = $this->params['measurement'];

        // Create a temporary directory to store image-  (TODO: Move this + other directory creation to installation script)
        $dir = HV_JP2_DIR . "/movies/";

        // Filename (From,To,By)
        $filename = implode("_", array($observatory, $instrument, $detector, $measurement, "F$startTime", "T$endTime", "B$cadence"));

        // Differentiate linked JPX files
        if ($links)
        $filename .= "L";

        // File extension
        $filename = str_replace(" ", "-", $filename) . "." . strtolower($format);

        // Filepath
        $output_file = $dir . $filename;

        // URL
        $url = HV_JP2_ROOT_URL . "/movies/" . $filename;

        // If the file doesn't exist already, create it
        if (!file_exists($output_file))
        {
            // Connect to database
            $imgIndex = new ImgIndex(new DbConnection());

            // Get data source id
            $source = $imgIndex->getSourceId($observatory, $instrument, $detector, $measurement);
            // Determine number of frames to grab
            $timeInSecs = $endTime - $startTime;
            $numFrames  = min(HV_MAX_MOVIE_FRAMES, ceil($timeInSecs / $cadence));

            // Timer
            $time = $startTime;

            $images = array();

            // Get nearest JP2 images to each time-step
            for ($i = 0; $i < $numFrames; $i++) {
                $isoDate = toISOString(parseUnixTimestamp($time));
                $jp2 = HV_JP2_DIR . $imgIndex->getJP2FilePath($isoDate, $source, $debug);
                array_push($images, $jp2);
                $time += $cadence;
            }

            // Remove redundant entries
            $images = array_unique($images);

            // Append filepaths to kdu_merge command
            $cmd = HV_KDU_MERGE_BIN . " -i ";
            foreach($images as $jp2) {
                $cmd .= "$jp2,";
            }

            // Drop trailing comma
            $cmd = substr($cmd, 0, -1);

            // Virtual JPX files
            if ($links)
            $cmd .= " -links";

            $cmd .= " -o $output_file";

            // MJ2 Creation
            if ($format == "MJ2")
            $cmd .= " -mj2_tracks P:0-@25";

            //die($cmd);

            // Execute kdu_merge command
            exec(HV_PATH_CMD . " " . escapeshellcmd($cmd), $output, $return);
        }

        // Output the file/jpip URL
        if ($jpip)
        echo $this->getJPIPURL($output_file);
        else
        echo $url;

        return 1;
    }
}

?>